<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DepartmentService
{
    public function __construct(private ActivityLogService $audit) {}

    public function create(array $data, Staff $actor): Department
    {
        Gate::forUser($actor)->authorize('create departments');

        return $this->save(new Department, $data, $actor);
    }

    public function update(Department $record, array $data, Staff $actor): Department
    {
        Gate::forUser($actor)->authorize('update departments');

        return $this->save($record, $data, $actor);
    }

    private function save(Department $record, array $data, Staff $actor): Department
    {
        return DB::transaction(function () use ($record, $data, $actor) {
            if ($record->exists) {
                $record = Department::whereKey($record->id)->lockForUpdate()->firstOrFail();
            }
            $data = Validator::make($data, ['code' => ['required', 'string', 'max:50', Rule::unique('departments', 'code')->ignore($record->id)], 'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($record->id)], 'description' => ['nullable', 'string', 'max:5000'], 'manager_id' => ['nullable', Rule::exists('staff', 'id')->whereNull('deleted_at')->where('status', 'ACTIVE')], 'is_active' => ['sometimes', 'boolean']])->validate();

            $old = $record->toArray();
            $event = $record->exists ? 'updated' : 'created';
            $record->fill($data)->save();
            $this->audit->record('Department.'.$event, $record, $actor, 'Department '.$event, $old, $record->toArray());

            return $record;
        });
    }
}
