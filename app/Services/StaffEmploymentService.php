<?php

namespace App\Services;

use App\Enums\EmploymentType;
use App\Enums\PayFrequency;
use App\Enums\StaffStatus;
use App\Models\Department;
use App\Models\JobPosition;
use App\Models\Staff;
use App\Models\StaffEmploymentDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StaffEmploymentService
{
    public function __construct(private ActivityLogService $audit, private StaffService $staffService) {}

    public function forStaff(Staff $staff, Staff $actor): array
    {
        Gate::forUser($actor)->authorize('view staff employment details');
        $detail = $staff->employmentDetail;
        if (! $detail) {
            return [];
        }
        $data = $detail->toArray();
        if ($actor->can('view staff salaries')) {
            foreach (StaffEmploymentDetail::SENSITIVE as $field) {
                $data[$field] = $detail->getAttribute($field);
            }
        }

        return $data;
    }

    public function save(Staff $staff, array $data, Staff $actor): StaffEmploymentDetail
    {
        Gate::forUser($actor)->authorize('update staff employment details');
        if (array_intersect(array_keys($data), StaffEmploymentDetail::SENSITIVE)) {
            Gate::forUser($actor)->authorize('update staff salaries');
        }

        return DB::transaction(function () use ($staff, $data, $actor) {
            $staff = Staff::whereKey($staff->id)->lockForUpdate()->firstOrFail();
            $detail = StaffEmploymentDetail::firstOrNew(['staff_id' => $staff->id]);
            $data = array_merge($detail->only(['department_id', 'job_position_id', 'employment_type', 'employment_date', 'confirmation_date', 'termination_date']), $data);
            foreach (['employment_date', 'confirmation_date', 'termination_date'] as $dateField) {
                if (($data[$dateField] ?? null) instanceof \DateTimeInterface) {
                    $data[$dateField] = $data[$dateField]->format('Y-m-d');
                }
            }
            $rules = [
                'department_id' => ['nullable', 'integer', 'exists:departments,id'],
                'job_position_id' => ['nullable', 'integer', 'exists:job_positions,id'],
                'employment_type' => ['required', Rule::enum(EmploymentType::class)],
                'employment_date' => ['required', 'date_format:Y-m-d'],
                'confirmation_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:employment_date'],
                'termination_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:employment_date', 'before_or_equal:today'],
                'basic_salary' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99', 'decimal:0,2'],
                'pay_frequency' => ['nullable', Rule::enum(PayFrequency::class)],
            ];
            foreach (['bank_name', 'bank_account_name', 'bank_account_number', 'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship'] as $field) {
                $rules[$field] = ['nullable', 'string', 'max:255'];
            }
            $data = Validator::make($data, $rules)->validate();
            $department = isset($data['department_id']) ? Department::whereKey($data['department_id'])->lockForUpdate()->firstOrFail() : null;
            $position = isset($data['job_position_id']) ? JobPosition::whereKey($data['job_position_id'])->lockForUpdate()->firstOrFail() : null;
            if (($department && ! $department->is_active) || ($position && (! $position->is_active || ! $department || $position->department_id !== $department->id))) {
                throw ValidationException::withMessages(['job_position_id' => 'Choose an active position in the selected active department.']);
            }
            $old = $detail->toArray();
            $detail->fill($data);
            $sensitiveChanged = array_intersect(array_keys($detail->getDirty()), StaffEmploymentDetail::SENSITIVE);
            $detail->save();
            if ($detail->termination_date) {
                $this->staffService->changeStatus($staff, StaffStatus::TERMINATED, $actor);
            }
            $this->audit->record('employment.updated', $detail, $actor, 'Employment details updated', $old, $detail->toArray());
            if ($sensitiveChanged) {
                $this->audit->record('employment.sensitive_updated', $detail, $actor, 'Salary or bank information updated', [], [], ['fields_changed' => $sensitiveChanged]);
            }

            return $detail;
        });
    }
}
