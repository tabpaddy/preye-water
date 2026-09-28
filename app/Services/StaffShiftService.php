<?php

namespace App\Services;

use App\Enums\StaffStatus;
use App\Models\Staff;
use App\Models\StaffShiftAssignment;
use App\Models\WorkShift;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StaffShiftService
{
    public function __construct(private ActivityLogService $audit) {}

    public function assign(array $data, Staff $actor): StaffShiftAssignment
    {
        return $this->save(new StaffShiftAssignment, $data, $actor);
    }

    public function update(StaffShiftAssignment $assignment, array $data, Staff $actor): StaffShiftAssignment
    {
        $data['staff_id'] = $assignment->staff_id;

        return $this->save($assignment, $data, $actor);
    }

    public function end(StaffShiftAssignment $assignment, string $date, Staff $actor): StaffShiftAssignment
    {
        return $this->update($assignment, ['staff_id' => $assignment->staff_id, 'work_shift_id' => $assignment->work_shift_id, 'effective_from' => $assignment->effective_from->toDateString(), 'effective_until' => $date, 'notes' => $assignment->notes], $actor);
    }

    public function effectiveShift(Staff $staff, string $date): ?WorkShift
    {
        return StaffShiftAssignment::with('workShift')->where('staff_id', $staff->id)->whereDate('effective_from', '<=', $date)->where(fn ($q) => $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date))->first()?->workShift;
    }

    private function save(StaffShiftAssignment $assignment, array $data, Staff $actor): StaffShiftAssignment
    {
        Gate::forUser($actor)->authorize('manage shift assignments');
        $data = Validator::make($data, [
            'staff_id' => ['required', 'integer', 'exists:staff,id'], 'work_shift_id' => ['required', 'integer', 'exists:work_shifts,id'],
            'effective_from' => ['required', 'date_format:Y-m-d'], 'effective_until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:effective_from'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ])->validate();

        return DB::transaction(function () use ($assignment, $data, $actor) {
            $staff = Staff::whereKey($data['staff_id'])->lockForUpdate()->firstOrFail();
            $shift = WorkShift::whereKey($data['work_shift_id'])->lockForUpdate()->firstOrFail();
            if ($staff->status !== StaffStatus::ACTIVE || ! $shift->is_active) {
                throw ValidationException::withMessages(['work_shift_id' => 'Staff and shift must be active.']);
            }
            if ($assignment->exists) {
                $assignment = StaffShiftAssignment::whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            }
            $overlap = StaffShiftAssignment::where('staff_id', $staff->id)->when($assignment->exists, fn ($q) => $q->where('id', '!=', $assignment->id))
                ->where('effective_from', '<=', $data['effective_until'] ?? '9999-12-31')
                ->where(fn ($q) => $q->whereNull('effective_until')->orWhere('effective_until', '>=', $data['effective_from']))->exists();
            if ($overlap) {
                throw ValidationException::withMessages(['effective_from' => 'This date range overlaps another shift assignment. End the earlier assignment first.']);
            }
            $old = $assignment->toArray();
            $assignment->fill($data);
            if (! $assignment->exists) {
                $assignment->assigned_by = $actor->id;
            }
            $assignment->save();
            $this->audit->record('shift.assignment_saved', $assignment, $actor, 'Shift assignment saved', $old, $assignment->toArray());

            return $assignment;
        });
    }
}
