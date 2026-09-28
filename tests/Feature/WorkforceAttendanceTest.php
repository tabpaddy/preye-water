<?php

namespace Tests\Feature;

use App\Enums\AttendanceAdjustmentStatus;
use App\Enums\AttendanceStatus;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\StaffShiftAssignment;
use App\Models\WorkShift;
use App\Services\AttendanceAdjustmentService;
use App\Services\AttendanceService;
use App\Services\StaffShiftService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkforceAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected Staff $admin;

    protected Staff $staff;

    protected WorkShift $shift;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = Staff::factory()->create();
        $this->admin->assignRole('Super Admin');
        $this->staff = Staff::factory()->create();
        $this->shift = WorkShift::factory()->create();
        app(StaffShiftService::class)->assign(['staff_id' => $this->staff->id, 'work_shift_id' => $this->shift->id, 'effective_from' => '2026-01-01'], $this->admin);
    }

    private function attendance(array $data = []): StaffAttendance
    {
        return app(AttendanceService::class)->create(array_merge(['staff_id' => $this->staff->id, 'attendance_date' => '2026-09-28', 'status' => 'PRESENT', 'clock_in_at' => '2026-09-28 08:23', 'clock_out_at' => '2026-09-28 16:12'], $data), $this->admin);
    }

    public function test_server_metrics_ignore_client_values(): void
    {
        $record = $this->attendance(['late_minutes' => 999, 'worked_minutes' => 999, 'overtime_minutes' => 999]);
        $this->assertSame(13, $record->late_minutes);
        $this->assertSame(439, $record->worked_minutes);
        $this->assertSame(12, $record->overtime_minutes);
        $this->assertSame('2026-09-28 07:23:00', $record->clock_in_at->format('Y-m-d H:i:s'));
        $this->assertSame(AttendanceStatus::LATE, $record->status);
    }

    public function test_grace_and_early_departure(): void
    {
        $record = $this->attendance(['clock_in_at' => '2026-09-28 08:05', 'clock_out_at' => '2026-09-28 15:45']);
        $this->assertSame(0, $record->late_minutes);
        $this->assertSame(15, $record->early_departure_minutes);
        $this->assertSame(430, $record->worked_minutes);
    }

    public function test_overnight_metrics_and_snapshot(): void
    {
        $this->shift->update(['start_time' => '22:00', 'end_time' => '06:00', 'is_overnight' => true, 'break_minutes' => 0]);
        $record = $this->attendance(['clock_in_at' => '2026-09-28 21:58', 'clock_out_at' => '2026-09-29 06:12']);
        $this->assertSame(494, $record->worked_minutes);
        $this->assertSame(12, $record->overtime_minutes);
        $this->assertSame(0, $record->late_minutes);
        $this->shift->update(['break_minutes' => 60]);
        app(AttendanceService::class)->recalculate($record);
        $this->assertSame(494, $record->worked_minutes);
    }

    public function test_no_shift_does_not_invent_metrics(): void
    {
        $staff = Staff::factory()->create();
        $record = $this->attendance(['staff_id' => $staff->id]);
        $this->assertNull($record->work_shift_id);
        $this->assertSame(0, $record->late_minutes);
        $this->assertSame(469, $record->worked_minutes);
        $this->assertSame(0, $record->overtime_minutes);
    }

    public function test_duplicate_attendance_rejected(): void
    {
        $this->attendance();
        $this->expectException(ValidationException::class);
        $this->attendance();
    }

    public function test_invalid_clock_order_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->attendance(['clock_out_at' => '2026-09-28 07:00']);
    }

    public function test_clock_in_and_clock_out(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-28 07:00:00', 'UTC'));
        $record = app(AttendanceService::class)->clockIn($this->staff, $this->admin);
        $this->travel(8)->hours();
        $record = app(AttendanceService::class)->clockOut($record, $this->admin);
        $this->assertSame(450, $record->worked_minutes);
        $this->assertDatabaseHas('activity_logs', ['event' => 'attendance.clocked_out']);
    }

    public function test_overlap_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(StaffShiftService::class)->assign(['staff_id' => $this->staff->id, 'work_shift_id' => $this->shift->id, 'effective_from' => '2026-06-01', 'effective_until' => '2026-07-01'], $this->admin);
    }

    public function test_effective_future_and_expired_assignments(): void
    {
        $assignment = StaffShiftAssignment::firstOrFail();
        $service = app(StaffShiftService::class);
        $service->end($assignment, '2026-06-30', $this->admin);
        $this->assertNull($service->effectiveShift($this->staff, '2026-07-01'));
        $service->assign(['staff_id' => $this->staff->id, 'work_shift_id' => $this->shift->id, 'effective_from' => '2026-10-01'], $this->admin);
        $this->assertSame($this->shift->id, $service->effectiveShift($this->staff, '2026-10-01')->id);
        $this->assertNull($service->effectiveShift($this->staff, '2025-12-31'));
    }

    public function test_adjustment_retains_original_until_approved_and_recalculates(): void
    {
        $record = $this->attendance();
        $service = app(AttendanceAdjustmentService::class);
        $requester = Staff::factory()->create();
        $requester->givePermissionTo('adjust attendance');
        $change = $service->request($record, ['adjustment_type' => 'CLOCK_IN', 'new_value' => '2026-09-28 08:00', 'reason' => 'Clock correction'], $requester);
        $this->assertSame(13, $record->fresh()->late_minutes);
        $approved = $service->approve($change, $this->admin);
        $this->assertSame('2026-09-28 07:23:00', $approved->old_value);
        $this->assertSame('2026-09-28 07:00:00', $approved->new_value);
        $this->assertSame(0, $record->fresh()->late_minutes);
        $this->assertSame(462, $record->fresh()->worked_minutes);
        $this->assertDatabaseHas('activity_logs', ['event' => 'attendance.adjusted']);
    }

    public function test_unauthorized_adjustment_approval(): void
    {
        $change = app(AttendanceAdjustmentService::class)->request($this->attendance(), ['adjustment_type' => 'CLOCK_IN', 'new_value' => '2026-09-28 08:00', 'reason' => 'Correction'], $this->admin);
        $this->expectException(AuthorizationException::class);
        app(AttendanceAdjustmentService::class)->approve($change, Staff::factory()->create());
    }

    public function test_rejection_preserves_attendance(): void
    {
        $record = $this->attendance();
        $reviewer = Staff::factory()->create();
        $reviewer->givePermissionTo('approve attendance adjustments');
        $service = app(AttendanceAdjustmentService::class);
        $change = $service->request($record, ['adjustment_type' => 'CLOCK_IN', 'new_value' => '2026-09-28 08:00', 'reason' => 'Correction'], $this->admin);
        $service->reject($change, $reviewer);
        $this->assertSame(13, $record->fresh()->late_minutes);
        $this->assertSame(AttendanceAdjustmentStatus::REJECTED, $change->fresh()->status);
    }

    public function test_stale_adjustment_rejected(): void
    {
        $record = $this->attendance();
        $reviewer = Staff::factory()->create();
        $reviewer->givePermissionTo('approve attendance adjustments');
        $service = app(AttendanceAdjustmentService::class);
        $a = $service->request($record, ['adjustment_type' => 'CLOCK_IN', 'new_value' => '2026-09-28 08:00', 'reason' => 'First'], $this->admin);
        $b = $service->request($record, ['adjustment_type' => 'CLOCK_IN', 'new_value' => '2026-09-28 08:05', 'reason' => 'Second'], $this->admin);
        $service->approve($a,$reviewer);
        $this->expectException(ValidationException::class);
        $service->approve($b,$reviewer);
    }
}
