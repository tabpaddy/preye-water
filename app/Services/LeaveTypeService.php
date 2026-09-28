<?php

namespace App\Services;

use App\Models\LeaveType;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LeaveTypeService
{
    public function __construct(private ActivityLogService $audit) {}

    public function create(array $data, Staff $actor): LeaveType
    {
        Gate::forUser($actor)->authorize('manage leave types');

        return $this->save(new LeaveType, $data, $actor);
    }

    public function update(LeaveType $record, array $data, Staff $actor): LeaveType
    {
        Gate::forUser($actor)->authorize('manage leave types');

        return $this->save($record, $data, $actor);
    }

    private function save(LeaveType $record, array $data, Staff $actor): LeaveType
    {
        return DB::transaction(function () use ($record, $data, $actor) {
            if ($record->exists) {
                $record = LeaveType::whereKey($record->id)->lockForUpdate()->firstOrFail();
            }
            $data = Validator::make($data, ['code' => ['required', 'string', 'max:50', Rule::unique('leave_types', 'code')->ignore($record->id)], 'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:5000'], 'is_paid' => ['sometimes', 'boolean'], 'requires_approval' => ['sometimes', 'boolean'], 'is_active' => ['sometimes', 'boolean']])->validate();

            $old = $record->toArray();
            $event = $record->exists ? 'updated' : 'created';
            $record->fill($data)->save();
            $this->audit->record('LeaveType.'.$event, $record, $actor, 'LeaveType '.$event, $old, $record->toArray());

            return $record;
        });
    }
}
