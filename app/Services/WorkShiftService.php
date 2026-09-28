<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\WorkShift;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WorkShiftService
{
    public function __construct(private ActivityLogService $audit) {}

    public function create(array $data, Staff $actor): WorkShift
    {
        Gate::forUser($actor)->authorize('create work shifts');

        return $this->save(new WorkShift, $data, $actor);
    }

    public function update(WorkShift $record, array $data, Staff $actor): WorkShift
    {
        Gate::forUser($actor)->authorize('update work shifts');

        return $this->save($record, $data, $actor);
    }

    private function save(WorkShift $record, array $data, Staff $actor): WorkShift
    {
        return DB::transaction(function () use ($record, $data, $actor) {
            if ($record->exists) {
                $record = WorkShift::whereKey($record->id)->lockForUpdate()->firstOrFail();
            }
            $data = Validator::make($data, ['code' => ['required', 'string', 'max:50', Rule::unique('work_shifts', 'code')->ignore($record->id)], 'name' => ['required', 'string', 'max:255'], 'start_time' => ['required', 'date_format:H:i'], 'end_time' => ['required', 'date_format:H:i'], 'grace_period_minutes' => ['required', 'integer', 'min:0', 'max:1440'], 'break_minutes' => ['required', 'integer', 'min:0', 'max:1440'], 'is_overnight' => ['required', 'boolean'], 'is_active' => ['sometimes', 'boolean']])->validate();
            $start = CarbonImmutable::parse($data['start_time']);
            $end = CarbonImmutable::parse($data['end_time']);
            if ($data['is_overnight']) {
                $end = $end->addDay();
            }
            $duration = $start->diffInMinutes($end, false);
            if ($duration <= 0 || $duration > 1440 || $data['break_minutes'] >= $duration || $data['grace_period_minutes'] >= $duration) {
                throw ValidationException::withMessages(['end_time' => 'Choose a positive shift of at most 24 hours, with break and grace shorter than the shift.']);
            }
            $old = $record->toArray();
            $event = $record->exists ? 'updated' : 'created';
            $record->fill($data)->save();
            $this->audit->record('WorkShift.'.$event, $record, $actor, 'WorkShift '.$event, $old, $record->toArray());

            return $record;
        });
    }
}
