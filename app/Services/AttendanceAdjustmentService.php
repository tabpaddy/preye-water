<?php

namespace App\Services;

use App\Enums\AdjustmentType;
use App\Enums\AttendanceAdjustmentStatus;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceAdjustment;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\WorkShift;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AttendanceAdjustmentService
{
    public function __construct(private AttendanceService $attendanceService, private AttendanceTimeService $time, private ActivityLogService $audit) {}

    private function field(AdjustmentType $type): string
    {
        return match ($type) {
            AdjustmentType::CLOCK_IN => 'clock_in_at',AdjustmentType::CLOCK_OUT => 'clock_out_at',AdjustmentType::STATUS => 'status',AdjustmentType::SHIFT => 'work_shift_id'
        };
    }

    private function value(StaffAttendance $attendance, AdjustmentType $type): ?string
    {
        $value = $attendance->getAttribute($this->field($type));

        return $value instanceof \BackedEnum ? $value->value : ($value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : ($value === null ? null : (string) $value));
    }

    public function request(StaffAttendance $attendance, array $data, Staff $actor): AttendanceAdjustment
    {
        Gate::forUser($actor)->authorize('adjust attendance');
        $data = Validator::make($data, ['adjustment_type' => ['required', Rule::enum(AdjustmentType::class)], 'new_value' => ['nullable', 'string', 'max:255'], 'reason' => ['required', 'string', 'max:5000']])->validate();
        $type = AdjustmentType::from($data['adjustment_type']);
        $value = $data['new_value'] ?? null;
        if (in_array($type, [AdjustmentType::CLOCK_IN, AdjustmentType::CLOCK_OUT]) && $value !== null) {
            Validator::make(['new_value' => $value], ['new_value' => ['date']])->validate();
            $value = $this->time->local($value)->utc()->format('Y-m-d H:i:s');
        }
        if ($type === AdjustmentType::STATUS) {
            Validator::make(['new_value' => $value], ['new_value' => ['required', Rule::enum(AttendanceStatus::class)]])->validate();
        }
        if ($type === AdjustmentType::SHIFT && $value !== null) {
            Validator::make(['new_value' => $value], ['new_value' => ['integer', 'exists:work_shifts,id']])->validate();
        }

        return DB::transaction(function () use ($attendance, $type, $value, $data, $actor) {
            Staff::withTrashed()->whereKey($attendance->staff_id)->lockForUpdate()->firstOrFail();
            $attendance = StaffAttendance::whereKey($attendance->id)->lockForUpdate()->firstOrFail();
            $adjustment = AttendanceAdjustment::create(['staff_attendance_id' => $attendance->id, 'adjustment_type' => $type, 'old_value' => $this->value($attendance, $type), 'new_value' => $value, 'reason' => $data['reason'], 'requested_by' => $actor->id, 'status' => AttendanceAdjustmentStatus::PENDING]);
            $this->audit->record('attendance.adjustment_requested', $adjustment, $actor, 'Attendance correction requested', [], $adjustment->toArray());

            return $adjustment;
        });
    }

    public function approve(AttendanceAdjustment $adjustment, Staff $actor): AttendanceAdjustment
    {
        return $this->decide($adjustment, $actor, true);
    }

    public function reject(AttendanceAdjustment $adjustment, Staff $actor): AttendanceAdjustment
    {
        return $this->decide($adjustment, $actor, false);
    }

    private function decide(AttendanceAdjustment $adjustment, Staff $actor, bool $approve): AttendanceAdjustment
    {
        Gate::forUser($actor)->authorize('approve attendance adjustments');

        return DB::transaction(function () use ($adjustment, $actor, $approve) {
            $attendance = $adjustment->attendance;
            Staff::withTrashed()->whereKey($attendance->staff_id)->lockForUpdate()->firstOrFail();
            $attendance = StaffAttendance::whereKey($attendance->id)->lockForUpdate()->firstOrFail();
            $adjustment = AttendanceAdjustment::whereKey($adjustment->id)->lockForUpdate()->firstOrFail();
            if ($adjustment->status !== AttendanceAdjustmentStatus::PENDING) {
                throw ValidationException::withMessages(['status' => 'This adjustment is already finalized.']);
            }
            if ($adjustment->requested_by === $actor->id) {
                throw ValidationException::withMessages(['approved_by' => 'Another authorized staff member must review your correction.']);
            }
            if ($approve) {
                if ($this->value($attendance, $adjustment->adjustment_type) !== $adjustment->old_value) {
                    throw ValidationException::withMessages(['new_value' => 'Attendance changed after this request. Submit a fresh correction.']);
                }
                $old = $attendance->toArray();
                $field = $this->field($adjustment->adjustment_type);
                $attendance->$field = $adjustment->new_value;
                if ($adjustment->adjustment_type === AdjustmentType::CLOCK_IN && $attendance->status !== AttendanceStatus::ON_LEAVE) {
                    $attendance->status = $attendance->clock_in_at ? AttendanceStatus::PRESENT : AttendanceStatus::ABSENT;
                }
                if ($adjustment->adjustment_type === AdjustmentType::SHIFT) {
                    $shift = $adjustment->new_value ? WorkShift::findOrFail($adjustment->new_value) : null;
                    $snapshot = $this->time->snapshot($shift);
                    if ($snapshot) {
                        $snapshot['timezone'] = $this->time->timezone();
                    }
                    $attendance->shift_snapshot = $snapshot;
                }
                $this->attendanceService->recalculate($attendance);
                $attendance->save();
                $this->audit->record('attendance.adjusted', $attendance, $actor, 'Approved attendance correction', $old, $attendance->toArray());
            }
            $adjustment->status = $approve ? AttendanceAdjustmentStatus::APPROVED : AttendanceAdjustmentStatus::REJECTED;
            $adjustment->approved_by = $actor->id;
            $adjustment->approved_at = now();
            $adjustment->save();
            $this->audit->record($approve ? 'attendance.adjustment_approved' : 'attendance.adjustment_rejected',$adjustment,$actor,$approve ? 'Correction approved' : 'Correction rejected');

            return $adjustment;
        });
    }
}
