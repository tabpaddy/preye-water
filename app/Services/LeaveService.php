<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveRequestStatus;
use App\Enums\StaffStatus;
use App\Models\LeaveType;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\StaffLeaveRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    public function __construct(private NumberSequenceService $numbers, private ActivityLogService $audit) {}

    public function submit(array $data, Staff $actor): StaffLeaveRequest
    {
        Gate::forUser($actor)->authorize('create leave requests');
        $data = Validator::make($data, [
            'staff_id' => ['required', 'integer', 'exists:staff,id'], 'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date' => ['required', 'date_format:Y-m-d'], 'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:5000'],
        ])->validate();

        return DB::transaction(function () use ($data, $actor) {
            $staff = Staff::whereKey($data['staff_id'])->lockForUpdate()->firstOrFail();
            $type = LeaveType::whereKey($data['leave_type_id'])->lockForUpdate()->firstOrFail();
            if ($staff->status !== StaffStatus::ACTIVE || ! $type->is_active) {
                throw ValidationException::withMessages(['leave_type_id' => 'Choose an active leave type and active staff member.']);
            }
            $this->checkOverlap($staff->id, $data['start_date'], $data['end_date']);
            $data['total_days'] = (int) CarbonImmutable::parse($data['start_date'])->diffInDays(CarbonImmutable::parse($data['end_date'])) + 1;
            $data['request_number'] = $this->numbers->next('LEAVE');
            $data['status'] = $type->requires_approval ? LeaveRequestStatus::PENDING : LeaveRequestStatus::APPROVED;
            if (! $type->requires_approval) {
                $this->checkAttendance($staff->id, $data['start_date'], $data['end_date']);
                $data['approved_at'] = now();
            }
            $record = StaffLeaveRequest::create($data);
            if (! $type->requires_approval) {
                $this->updateAbsences($record, $actor);
            }
            $this->audit->record('leave.submitted', $record, $actor, 'Leave request submitted', [], $record->toArray());
            if (! $type->requires_approval) {
                $this->audit->record('leave.auto_approved', $record, $actor, 'Leave type does not require approval');
            }

            return $record;
        });
    }

    public function approve(StaffLeaveRequest $record, Staff $actor): StaffLeaveRequest
    {
        return $this->transition($record, $actor, LeaveRequestStatus::APPROVED);
    }

    public function reject(StaffLeaveRequest $record, string $reason, Staff $actor): StaffLeaveRequest
    {
        Validator::make(['rejection_reason' => $reason], ['rejection_reason' => ['required', 'string', 'max:5000']])->validate();

        return $this->transition($record, $actor, LeaveRequestStatus::REJECTED, $reason);
    }

    public function cancel(StaffLeaveRequest $record, Staff $actor): StaffLeaveRequest
    {
        return $this->transition($record, $actor, LeaveRequestStatus::CANCELLED);
    }

    private function transition(StaffLeaveRequest $record, Staff $actor, LeaveRequestStatus $status, ?string $reason = null): StaffLeaveRequest
    {
        Gate::forUser($actor)->authorize($status === LeaveRequestStatus::CANCELLED ? 'cancel leave requests' : 'approve leave requests');

        return DB::transaction(function () use ($record, $actor, $status, $reason) {
            $staff = Staff::withTrashed()->whereKey($record->staff_id)->lockForUpdate()->firstOrFail();
            $record = StaffLeaveRequest::whereKey($record->id)->lockForUpdate()->firstOrFail();
            $allowed = $record->status === LeaveRequestStatus::PENDING || ($status === LeaveRequestStatus::CANCELLED && $record->status === LeaveRequestStatus::APPROVED);
            if (! $allowed) {
                throw ValidationException::withMessages(['status' => 'This leave request is already finalized.']);
            }
            $start = $record->start_date->toDateString();
            $end = $record->end_date->toDateString();
            $old = $record->toArray();
            if ($status === LeaveRequestStatus::APPROVED) {
                if ($staff->trashed() || $staff->status !== StaffStatus::ACTIVE || ! $record->leaveType->is_active) {
                    throw ValidationException::withMessages(['status' => 'Staff and leave type must still be active.']);
                }
                if ($record->staff_id === $actor->id) {
                    throw ValidationException::withMessages(['approved_by' => 'Another authorized staff member must approve your leave.']);
                }
                $this->checkOverlap($staff->id, $start, $end, $record->id);
                $this->checkAttendance($staff->id, $start, $end);
                $record->approved_by = $actor->id;
                $record->approved_at = now();
                $this->updateAbsences($record, $actor);
            }
            if ($status === LeaveRequestStatus::CANCELLED && $record->status === LeaveRequestStatus::APPROVED && StaffAttendance::where('staff_id', $staff->id)->whereBetween('attendance_date', [$start, $end])->exists()) {
                throw ValidationException::withMessages(['status' => 'Approved leave with attendance history cannot be cancelled in this phase.']);
            }
            $record->status = $status;
            $record->rejection_reason = $reason;
            $record->save();
            $this->audit->record('leave.'.strtolower($status->value), $record, $actor, 'Leave '.strtolower($status->value), $old, $record->toArray());

            return $record;
        });
    }

    private function checkOverlap(int $staffId, string $start, string $end, ?int $ignore = null): void
    {
        if (StaffLeaveRequest::where('staff_id', $staffId)->whereIn('status', [LeaveRequestStatus::PENDING, LeaveRequestStatus::APPROVED])->when($ignore, fn ($q) => $q->where('id', '!=', $ignore))->where('start_date', '<=', $end)->where('end_date', '>=', $start)->exists()) {
            throw ValidationException::withMessages(['start_date' => 'This request overlaps pending or approved leave.']);
        }
    }

    private function checkAttendance(int $staffId, string $start, string $end): void
    {
        if (StaffAttendance::where('staff_id', $staffId)->whereBetween('attendance_date', [$start, $end])->where(fn ($q) => $q->whereNotNull('clock_in_at')->orWhereNotNull('clock_out_at'))->exists()) {
            throw ValidationException::withMessages(['start_date' => 'Leave conflicts with recorded working time.']);
        }
    }

    private function updateAbsences(StaffLeaveRequest $leave, Staff $actor): void
    {
        $records = StaffAttendance::where('staff_id', $leave->staff_id)->whereBetween('attendance_date', [$leave->start_date->toDateString(), $leave->end_date->toDateString()])->lockForUpdate()->get();
        foreach ($records as $attendance) {
            $old = $attendance->toArray();
            $attendance->status = AttendanceStatus::ON_LEAVE;
            $attendance->save();
            $this->audit->record('attendance.leave_applied',$attendance,$actor,'Approved leave applied',$old,$attendance->toArray());
        }
    }
}
