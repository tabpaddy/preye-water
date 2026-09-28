<?php

namespace App\Services;

use App\Enums\AttendanceMethod;
use App\Enums\AttendanceStatus;
use App\Enums\LeaveRequestStatus;
use App\Enums\StaffStatus;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\StaffLeaveRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(private StaffShiftService $shifts, private AttendanceTimeService $time, private ActivityLogService $audit) {}

    public function clockIn(Staff $staff, Staff $actor): StaffAttendance
    {
        $localNow = now($this->time->timezone());
        $date = $localNow->toDateString();
        $previousDate = $localNow->copy()->subDay()->toDateString();
        $previous = $this->shifts->effectiveShift($staff, $previousDate);
        if ($previous?->is_overnight && $localNow->lte($this->time->local($date.' '.$previous->end_time))) {
            $date = $previousDate;
        }

        return $this->create(['staff_id' => $staff->id, 'attendance_date' => $date, 'clock_in_at' => now()->toIso8601String(), 'status' => 'PRESENT'], $actor);
    }

    public function create(array $data, Staff $actor): StaffAttendance
    {
        Gate::forUser($actor)->authorize('record attendance');
        $data = Validator::make($data, [
            'staff_id' => ['required', 'integer', 'exists:staff,id'], 'attendance_date' => ['required', 'date_format:Y-m-d'],
            'status' => ['required', Rule::enum(AttendanceStatus::class)], 'clock_in_at' => ['nullable', 'date'], 'clock_out_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ])->validate();

        return DB::transaction(function () use ($data, $actor) {
            $staff = Staff::whereKey($data['staff_id'])->lockForUpdate()->firstOrFail();
            if ($staff->status !== StaffStatus::ACTIVE) {
                throw ValidationException::withMessages(['staff_id' => 'Attendance can only be recorded for active staff.']);
            }
            if (StaffAttendance::where('staff_id', $staff->id)->whereDate('attendance_date', $data['attendance_date'])->exists()) {
                throw ValidationException::withMessages(['attendance_date' => 'Attendance already exists. Use the adjustment workflow.']);
            }
            $shift = $this->shifts->effectiveShift($staff, $data['attendance_date']);
            $snapshot = $this->time->snapshot($shift);
            if ($snapshot) {
                $snapshot['timezone'] = $this->time->timezone();
            }
            $attendance = new StaffAttendance($data);
            $attendance->work_shift_id = $shift?->id;
            $attendance->shift_snapshot = $snapshot;
            foreach (['clock_in_at', 'clock_out_at'] as $field) {
                $attendance->$field = filled($data[$field] ?? null) ? $this->time->local($data[$field])->utc() : null;
            }
            $attendance->clock_in_method = $attendance->clock_in_at ? AttendanceMethod::ADMIN : null;
            $attendance->clock_out_method = $attendance->clock_out_at ? AttendanceMethod::ADMIN : null;
            $this->recalculate($attendance);
            $attendance->save();
            $this->audit->record('attendance.created', $attendance, $actor, 'Attendance recorded', [], $attendance->toArray());

            return $attendance;
        });
    }

    public function clockOut(StaffAttendance $attendance, Staff $actor): StaffAttendance
    {
        Gate::forUser($actor)->authorize('record attendance');

        return DB::transaction(function () use ($attendance, $actor) {
            Staff::withTrashed()->whereKey($attendance->staff_id)->lockForUpdate()->firstOrFail();
            $attendance = StaffAttendance::whereKey($attendance->id)->lockForUpdate()->firstOrFail();
            if (! $attendance->clock_in_at || $attendance->clock_out_at) {
                throw ValidationException::withMessages(['clock_out_at' => 'Only an open attendance can be clocked out.']);
            }
            $old = $attendance->toArray();
            $attendance->clock_out_at = now();
            $attendance->clock_out_method = AttendanceMethod::ADMIN;
            $this->recalculate($attendance);
            $attendance->save();
            $this->audit->record('attendance.clocked_out', $attendance, $actor, 'Staff clocked out', $old, $attendance->toArray());

            return $attendance;
        });
    }

    /** Called only by transactional attendance workflows, never from UI calculations. */
    public function recalculate(StaffAttendance $attendance): void
    {
        $leave = StaffLeaveRequest::where('staff_id', $attendance->staff_id)->where('status', LeaveRequestStatus::APPROVED)->where('start_date', '<=', $attendance->attendance_date->toDateString())->where('end_date', '>=', $attendance->attendance_date->toDateString())->exists();
        if ($leave) {
            if ($attendance->clock_in_at || $attendance->clock_out_at) {
                throw ValidationException::withMessages(['attendance_date' => 'Approved leave conflicts with worked attendance.']);
            }
            $attendance->status = AttendanceStatus::ON_LEAVE;
        } elseif ($attendance->status === AttendanceStatus::ON_LEAVE) {
            throw ValidationException::withMessages(['status' => 'ON_LEAVE requires an approved leave request.']);
        }
        if ($attendance->clock_in_at) {
            if (in_array($attendance->status, [AttendanceStatus::ABSENT, AttendanceStatus::ON_LEAVE, AttendanceStatus::OFF_DAY])) {
                throw ValidationException::withMessages(['status' => 'This status cannot have clock times.']);
            }
            $local = $attendance->clock_in_at->setTimezone($attendance->shift_snapshot['timezone'] ?? $this->time->timezone());
            $anchor = $attendance->attendance_date->toDateString();
            $last = $attendance->shift_snapshot['is_overnight'] ?? false ? $attendance->attendance_date->copy()->addDay()->toDateString() : $anchor;
            if ($local->toDateString() < $anchor || $local->toDateString() > $last) {
                throw ValidationException::withMessages(['clock_in_at' => 'Clock in must fall on the attendance date or its overnight continuation.']);
            }
        } elseif (in_array($attendance->status, [AttendanceStatus::PRESENT, AttendanceStatus::LATE, AttendanceStatus::HALF_DAY])) {
            throw ValidationException::withMessages(['clock_in_at' => 'Worked attendance requires a clock-in time.']);
        }
        $attendance->fill($this->time->calculate($attendance));
        if ($attendance->clock_in_at && $attendance->status !== AttendanceStatus::HALF_DAY) {
            $attendance->status = $attendance->late_minutes > 0 ? AttendanceStatus::LATE : AttendanceStatus::PRESENT;
        }
    }
}
