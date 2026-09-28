<?php

namespace App\Services;

use App\Models\JobPosition;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class JobPositionService
{
    public function __construct(private ActivityLogService $audit) {}

    public function create(array $data, Staff $actor): JobPosition
    {
        Gate::forUser($actor)->authorize('create job positions');

        return $this->save(new JobPosition, $data, $actor);
    }

    public function update(JobPosition $record, array $data, Staff $actor): JobPosition
    {
        Gate::forUser($actor)->authorize('update job positions');

        return $this->save($record, $data, $actor);
    }

    private function save(JobPosition $record, array $data, Staff $actor): JobPosition
    {
        return DB::transaction(function () use ($record, $data, $actor) {
            if ($record->exists) {
                $record = JobPosition::whereKey($record->id)->lockForUpdate()->firstOrFail();
            }
            $data = Validator::make($data, ['code' => ['required', 'string', 'max:50', Rule::unique('job_positions', 'code')->ignore($record->id)], 'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:5000'], 'department_id' => ['required', Rule::exists('departments', 'id')->where('is_active', true)], 'is_active' => ['sometimes', 'boolean']])->validate();
            if ($record->exists && $record->department_id != $data['department_id'] && $record->staffEmploymentDetails()->exists()) {
                throw ValidationException::withMessages(['department_id' => 'An occupied position cannot move departments. Create a new position.']);
            }
            $old = $record->toArray();
            $event = $record->exists ? 'updated' : 'created';
            $record->fill($data)->save();
            $this->audit->record('JobPosition.'.$event, $record, $actor, 'JobPosition '.$event, $old, $record->toArray());

            return $record;
        });
    }
}
