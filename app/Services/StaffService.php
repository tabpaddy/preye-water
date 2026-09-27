<?php

namespace App\Services;

use App\Enums\StaffStatus;
use App\Models\Person;
use App\Models\Staff;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StaffService
{
    public function __construct(private NumberSequenceService $numbers, private ActivityLogService $audit, private StaffRoleService $roles) {}

    public function create(array $data, Staff $actor): Staff
    {
        Gate::forUser($actor)->authorize('create', Staff::class);

        return $this->createAccount($data, $actor);
    }

    public function bootstrap(array $data): Staff
    {
        abort_unless(app()->runningInConsole() && ! Staff::whereHas('roles', fn ($q) => $q->where('name', 'Super Admin')->where('guard_name', 'staff'))->exists(), 403);

        return DB::transaction(function () use ($data) {
            $staff = $this->createAccount(Arr::except($data, ['roles']), null);
            $staff->assignRole('Super Admin');
            $this->audit->record('staff.roles_changed', $staff, null, 'Initial Super Admin assigned', [], ['roles' => ['Super Admin']]);

            return $staff;
        });
    }

    private function createAccount(array $data, ?Staff $actor): Staff
    {
        $data = $this->validate($data, true);

        return DB::transaction(function () use ($data, $actor) {
            $person = Person::create(Arr::only($data, (new Person)->getFillable()));
            $staff = Staff::create(['person_id' => $person->id, 'staff_number' => $this->numbers->next('STAFF'), 'password' => $data['password'], 'status' => $data['status'] ?? StaffStatus::ACTIVE, 'password_changed_at' => now()]);
            if (isset($data['roles']) && $actor) {
                $this->roles->syncRoles($staff, $data['roles'], $actor);
            }
            $this->audit->record('staff.created', $staff, $actor, 'Staff account created', [], Arr::except($data, ['password', 'password_confirmation']));

            return $staff;
        });
    }

    public function update(Staff $staff, array $data, Staff $actor): Staff
    {
        Gate::forUser($actor)->authorize('update', $staff);
        $data = $this->validate($data, false);

        return DB::transaction(function () use ($staff, $data, $actor) {
            $staff = Staff::whereKey($staff->id)->lockForUpdate()->firstOrFail();
            $old = array_merge($staff->person->only((new Person)->getFillable()), ['status' => $staff->status->value]);
            $staff->person->update(Arr::only($data, (new Person)->getFillable()));
            if (array_key_exists('email', $data) && $old['email'] !== $data['email']) {
                $staff->email_verified_at = null;
            }
            if (filled($data['password'] ?? null)) {
                $staff->password = $data['password'];
                $staff->password_changed_at = now();
                $staff->remember_token = null;
            }
            if (isset($data['status'])) {
                $staff->status = $data['status'];
            }
            $staff->save();
            if (isset($data['roles'])) {
                $this->roles->syncRoles($staff, $data['roles'], $actor);
            }
            $this->audit->record('staff.updated', $staff, $actor, 'Staff account updated', $old, Arr::except($data, ['password', 'password_confirmation']));
            if ($old['status'] !== $staff->status->value) {
                $this->audit->record('staff.status_changed', $staff, $actor, 'Staff status changed', ['status' => $old['status']], ['status' => $staff->status->value]);
            }

            return $staff->refresh();
        });
    }

    public function changeStatus(Staff $staff, StaffStatus $status, Staff $actor): Staff
    {
        return $this->update($staff, ['status' => $status->value], $actor);
    }

    public function delete(Staff $staff, Staff $actor): void
    {
        Gate::forUser($actor)->authorize('delete', $staff);
        DB::transaction(function () use ($staff, $actor) {
            $this->audit->record('staff.deleted', $staff, $actor, 'Staff account archived');
            $staff->delete();
        });
    }

    private function validate(array $data, bool $creating): array
    {
        $rules = [
            'first_name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'last_name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'status' => ['sometimes', Rule::enum(StaffStatus::class)],
            'password' => [$creating ? 'required' : 'nullable', 'confirmed', Password::min(12)],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')->where('guard_name', 'staff')],
        ];
        foreach (['middle_name', 'phone', 'alternate_phone', 'gender'] as $field) {
            $rules[$field] = ['nullable', 'string', 'max:255'];
        }

        return Validator::make($data, $rules)->validate();
    }
}
