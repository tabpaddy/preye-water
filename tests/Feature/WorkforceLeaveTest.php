<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveRequestStatus;
use App\Models\LeaveType;
use App\Models\Staff;
use App\Models\StaffLeaveRequest;
use App\Services\AttendanceService;
use App\Services\LeaveService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkforceLeaveTest extends TestCase
{
    use RefreshDatabase;

    protected Staff $admin;

    protected Staff $staff;

    protected LeaveType $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = Staff::factory()->create();
        $this->admin->assignRole('Super Admin');
        $this->staff = Staff::factory()->create();
        $this->type = LeaveType::factory()->create();
    }

    private function submit(array $changes = []): StaffLeaveRequest
    {
        return app(LeaveService::class)->submit(array_merge(['staff_id' => $this->staff->id, 'leave_type_id' => $this->type->id, 'start_date' => '2026-10-01', 'end_date' => '2026-10-03'], $changes), $this->admin);
    }

    public function test_number_days_and_approval(): void
    {
        $leave = $this->submit(['total_days' => 999, 'request_number' => 'FORGED', 'status' => 'APPROVED']);
        $this->assertStringStartsWith('LEV-', $leave->request_number);
        $this->assertSame('3.00', $leave->total_days);
        $this->assertSame(LeaveRequestStatus::PENDING, $leave->status);
        $leave = app(LeaveService::class)->approve($leave, $this->admin);
        $this->assertSame(LeaveRequestStatus::APPROVED, $leave->status);
        $this->assertSame($this->admin->id, $leave->approved_by);
        $this->assertDatabaseHas('activity_logs', ['event' => 'leave.approved']);
    }

    public function test_date_order_validation(): void
    {
        $this->expectException(ValidationException::class);
        $this->submit(['end_date' => '2026-09-30']);
    }

    public function test_inactive_type_rejected(): void
    {
        $this->type->update(['is_active' => false]);
        $this->expectException(ValidationException::class);
        $this->submit();
    }

    public function test_overlap_pending_and_approved_rejected(): void
    {
        $this->submit();
        $this->expectException(ValidationException::class);
        $this->submit(['start_date' => '2026-10-03', 'end_date' => '2026-10-05']);
    }

    public function test_reject_and_cancel_are_audited(): void
    {
        $leave = app(LeaveService::class)->reject($this->submit(), 'Staffing coverage', $this->admin);
        $this->assertSame('Staffing coverage', $leave->rejection_reason);
        $this->assertSame(LeaveRequestStatus::REJECTED, $leave->status);
        $next = app(LeaveService::class)->cancel($this->submit(), $this->admin);
        $this->assertSame(LeaveRequestStatus::CANCELLED, $next->status);
        $this->assertDatabaseHas('activity_logs', ['event' => 'leave.cancelled']);
    }

    public function test_unauthorized_approval(): void
    {
        $record = $this->submit();
        $this->expectException(AuthorizationException::class);
        app(LeaveService::class)->approve($record, Staff::factory()->create());
    }

    public function test_finalized_request_cannot_be_approved_twice(): void
    {
        $record = $this->submit();
        app(LeaveService::class)->approve($record, $this->admin);
        $this->expectException(ValidationException::class);
        app(LeaveService::class)->approve($record, $this->admin);
    }

    public function test_leave_converts_existing_absence_and_informs_future_attendance(): void
    {
        $attendance = app(AttendanceService::class)->create(['staff_id' => $this->staff->id, 'attendance_date' => '2026-10-01', 'status' => 'ABSENT'], $this->admin);
        app(LeaveService::class)->approve($this->submit(), $this->admin);
        $this->assertSame(AttendanceStatus::ON_LEAVE, $attendance->fresh()->status);
        $next = app(AttendanceService::class)->create(['staff_id' => $this->staff->id, 'attendance_date' => '2026-10-02', 'status' => 'ABSENT'], $this->admin);
        $this->assertSame(AttendanceStatus::ON_LEAVE, $next->status);
    }

    public function test_worked_attendance_blocks_leave_approval(): void
    {
        app(AttendanceService::class)->create(['staff_id' => $this->staff->id, 'attendance_date' => '2026-10-01', 'status' => 'PRESENT', 'clock_in_at' => '2026-10-01 08:00'], $this->admin);
        $record = $this->submit();
        $this->expectException(ValidationException::class);
        app(LeaveService::class)->approve($record, $this->admin);
    }

    public function test_type_without_approval_is_auto_approved(): void
    {
        $this->type->update(['requires_approval' => false]);
        $record = $this->submit();
        $this->assertSame(LeaveRequestStatus::APPROVED, $record->status);
        $this->assertNotNull($record->approved_at);
        $this->assertNull($record->approved_by);
    }
}
