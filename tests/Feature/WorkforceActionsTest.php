<?php

namespace Tests\Feature;

use App\Enums\AttendanceAdjustmentStatus;
use App\Enums\AttendanceStatus;
use App\Enums\LeaveRequestStatus;
use App\Filament\Resources\AttendanceAdjustments\Pages\ListAttendanceAdjustments;
use App\Filament\Resources\StaffAttendances\Pages\ListStaffAttendances;
use App\Filament\Resources\StaffLeaveRequests\Pages\ListStaffLeaveRequests;
use App\Filament\Resources\WorkShifts\Pages\CreateWorkShift;
use App\Filament\Resources\WorkShifts\Pages\EditWorkShift;
use App\Models\AttendanceAdjustment;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\StaffLeaveRequest;
use App\Models\WorkShift;
use App\Services\AttendanceAdjustmentService;
use App\Services\AttendanceService;
use App\Services\StaffShiftService;
use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WorkforceActionsTest extends TestCase
{
    use RefreshDatabase;

    protected Staff $admin;

    protected Staff $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Filament::setCurrentPanel(Filament::getPanel('staff'));
        $this->admin = Staff::factory()->create();
        $this->admin->assignRole('Super Admin');
        $this->employee = Staff::factory()->create();
    }

    public function test_leave_approve_action_requires_permission(): void
    {
        $leave = StaffLeaveRequest::factory()->create(['staff_id' => $this->employee->id]);
        $viewer = Staff::factory()->create();
        $viewer->givePermissionTo('view leave requests');
        $this->actingAs($viewer, 'staff');
        Livewire::test(ListStaffLeaveRequests::class)->assertActionHidden(TestAction::make('approve')->table($leave))->mountAction(TestAction::make('approve')->table($leave))->callMountedAction();
        $this->assertSame(LeaveRequestStatus::PENDING, $leave->fresh()->status);
        $this->flushSession();
        $this->actingAs($this->admin, 'staff');
        Livewire::test(ListStaffLeaveRequests::class)->callAction(TestAction::make('approve')->table($leave))->assertHasNoActionErrors();
        $this->assertSame(LeaveRequestStatus::APPROVED, $leave->fresh()->status);
    }

    public function test_attendance_correction_action_and_approval_authorization(): void
    {
        $record = app(AttendanceService::class)->create(['staff_id' => $this->employee->id, 'attendance_date' => '2026-09-28', 'clock_in_at' => '2026-09-28 08:23', 'status' => 'PRESENT'], $this->admin);
        $this->actingAs($this->admin, 'staff');
        Livewire::test(ListStaffAttendances::class)->callAction(TestAction::make('requestAdjustment')->table($record), ['adjustment_type' => 'CLOCK_IN', 'clock_value' => '2026-09-28T08:00', 'reason' => 'Clock error'])->assertHasNoActionErrors();
        $change = AttendanceAdjustment::firstOrFail();
        $reviewer = Staff::factory()->create();
        $reviewer->givePermissionTo('view attendance');
        $this->flushSession();
        $this->actingAs($reviewer, 'staff');
        Livewire::test(ListAttendanceAdjustments::class)->assertActionHidden(TestAction::make('approve')->table($change))->mountAction(TestAction::make('approve')->table($change))->callMountedAction();
        $this->assertSame(AttendanceAdjustmentStatus::PENDING, $change->fresh()->status);
        $reviewer->givePermissionTo('approve attendance adjustments');
        Livewire::test(ListAttendanceAdjustments::class)->callAction(TestAction::make('approve')->table($change))->assertHasNoActionErrors();
        $this->assertSame('2026-09-28 07:00:00', $record->fresh()->clock_in_at->format('Y-m-d H:i:s'));
    }

    public function test_work_shift_create_and_edit(): void
    {
        $this->actingAs($this->admin, 'staff');
        Livewire::test(CreateWorkShift::class)->fillForm(['name' => 'Night', 'code' => 'NIGHT', 'start_time' => '22:00', 'end_time' => '06:00', 'break_minutes' => 30, 'grace_period_minutes' => 10, 'is_overnight' => true, 'is_active' => true])->call('create')->assertHasNoFormErrors();
        $shift = WorkShift::firstOrFail();
        Livewire::test(EditWorkShift::class, ['record' => $shift->uuid])->fillForm(['break_minutes' => 45])->call('save')->assertHasNoFormErrors();
        $this->assertSame(45, $shift->fresh()->break_minutes);
    }

    public function test_overnight_after_midnight_clock_in_uses_previous_date(): void
    {
        $shift = WorkShift::factory()->create(['start_time' => '22:00', 'end_time' => '06:00', 'is_overnight' => true]);
        app(StaffShiftService::class)->assign(['staff_id' => $this->employee->id, 'work_shift_id' => $shift->id, 'effective_from' => '2026-09-01'], $this->admin);
        $this->travelTo(CarbonImmutable::parse('2026-09-29 00:00:00', 'UTC'));
        $record = app(AttendanceService::class)->clockIn($this->employee, $this->admin);
        $this->assertSame('2026-09-28', $record->attendance_date->toDateString());
        $this->assertSame(170, $record->late_minutes);
    }

    public function test_correction_can_supply_missing_clock_in_on_absence(): void
    {
        $record = StaffAttendance::factory()->create(['staff_id' => $this->employee->id]);
        $change = app(AttendanceAdjustmentService::class)->request($record, ['adjustment_type' => 'CLOCK_IN', 'new_value' => '2026-09-28 08:00', 'reason' => 'Missed clock'], $this->admin);
        $reviewer = Staff::factory()->create();
        $reviewer->givePermissionTo('approve attendance adjustments');
        app(AttendanceAdjustmentService::class)->approve($change, $reviewer);
        $this->assertSame(AttendanceStatus::PRESENT, $record->fresh()->status);
    }
}
