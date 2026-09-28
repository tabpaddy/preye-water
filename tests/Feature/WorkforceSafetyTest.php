<?php

namespace Tests\Feature;

use App\Enums\LeaveRequestStatus;
use App\Enums\StaffStatus;
use App\Models\Department;
use App\Models\JobPosition;
use App\Models\LeaveType;
use App\Models\Staff;
use App\Services\AttendanceAdjustmentService;
use App\Services\AttendanceService;
use App\Services\LeaveService;
use App\Services\StaffEmploymentService;
use App\Services\StaffService;
use App\Services\WorkShiftService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkforceSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected Staff $admin;

    protected Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = Staff::factory()->create();
        $this->admin->assignRole('Super Admin');
        $this->staff = Staff::factory()->create();
    }

    public function test_terminated_employment_cannot_be_reactivated_from_account_edit(): void
    {
        app(StaffEmploymentService::class)->save($this->staff, ['employment_type' => 'FULL_TIME', 'employment_date' => '2026-01-01', 'termination_date' => '2026-09-01'], $this->admin);
        $this->expectException(ValidationException::class);
        app(StaffService::class)->update($this->staff, ['status' => 'ACTIVE'], $this->admin);
    }

    public function test_department_only_update_validates_existing_position(): void
    {
        $position = JobPosition::factory()->create();
        app(StaffEmploymentService::class)->save($this->staff, ['department_id' => $position->department_id, 'job_position_id' => $position->id, 'employment_type' => 'FULL_TIME', 'employment_date' => '2026-01-01'], $this->admin);
        $this->expectException(ValidationException::class);
        app(StaffEmploymentService::class)->save($this->staff, ['department_id' => Department::factory()->create()->id], $this->admin);
    }

    public function test_invalid_overnight_configuration_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(WorkShiftService::class)->create(['name' => 'Bad', 'code' => 'BAD', 'start_time' => '22:00', 'end_time' => '06:00', 'grace_period_minutes' => 0, 'break_minutes' => 0, 'is_overnight' => false], $this->admin);
    }

    public function test_adjustment_cannot_be_self_approved(): void
    {
        $attendance = app(AttendanceService::class)->create(['staff_id' => $this->staff->id, 'attendance_date' => '2026-09-28', 'clock_in_at' => '2026-09-28 08:00', 'status' => 'PRESENT'], $this->admin);
        $service = app(AttendanceAdjustmentService::class);
        $change = $service->request($attendance, ['adjustment_type' => 'CLOCK_IN', 'new_value' => '2026-09-28 08:05', 'reason' => 'Correction'], $this->admin);
        $this->expectException(ValidationException::class);
        $service->approve($change, $this->admin);
    }

    public function test_approved_leave_cannot_be_cancelled_after_attendance_is_recorded(): void
    {
        $type = LeaveType::factory()->create();
        $leave = app(LeaveService::class)->submit(['staff_id' => $this->staff->id, 'leave_type_id' => $type->id, 'start_date' => '2026-10-01', 'end_date' => '2026-10-02'], $this->admin);
        app(LeaveService::class)->approve($leave, $this->admin);
        app(AttendanceService::class)->create(['staff_id' => $this->staff->id, 'attendance_date' => '2026-10-01', 'status' => 'ABSENT'], $this->admin);
        $this->expectException(ValidationException::class);
        app(LeaveService::class)->cancel($leave, $this->admin);
    }

    public function test_unconsumed_approved_leave_can_be_cancelled(): void
    {
        $leave = app(LeaveService::class)->submit(['staff_id' => $this->staff->id, 'leave_type_id' => LeaveType::factory()->create()->id, 'start_date' => '2026-10-01', 'end_date' => '2026-10-02'], $this->admin);
        app(LeaveService::class)->approve($leave, $this->admin);
        $this->assertSame(LeaveRequestStatus::CANCELLED, app(LeaveService::class)->cancel($leave, $this->admin)->status);
    }

    public function test_termination_rolls_back_if_actor_cannot_change_account_status(): void
    {
        $actor = Staff::factory()->create();
        $actor->givePermissionTo('update staff employment details');
        try {
            app(StaffEmploymentService::class)->save($this->staff, ['employment_type' => 'FULL_TIME', 'employment_date' => '2026-01-01', 'termination_date' => '2026-09-01'], $actor);
            $this->fail('Expected denial');
        } catch (AuthorizationException) {
        }
        $this->assertDatabaseCount('staff_employment_details', 0);
        $this->assertSame(StaffStatus::ACTIVE, $this->staff->fresh()->status);
    }
}
