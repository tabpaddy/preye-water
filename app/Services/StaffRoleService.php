<?php

namespace App\Services;

use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class StaffRoleService
{
    public function __construct(private ActivityLogService $audit) {}

    public function syncRoles(Staff $staff, array $roleIds, Staff $actor): void
    {
        Gate::forUser($actor)->authorize('manage staff roles');
        $roles = Role::whereIn('id', $roleIds)->where('guard_name', 'staff')->get();
        if ($roles->count() !== count(array_unique($roleIds))) {
            throw ValidationException::withMessages(['roles' => 'Choose valid staff roles.']);
        }
        foreach ($roles as $role) {
            if ($role->name === 'Super Admin') {
                Gate::forUser($actor)->authorize('grant super admin');
            }
            foreach ($role->permissions as $permission) {
                Gate::forUser($actor)->authorize($permission->name);
            }
        }
        DB::transaction(function () use ($staff, $roles, $actor) {
            $old = $staff->roles()->pluck('name')->all();
            $staff->syncRoles($roles);
            $this->audit->record('staff.roles_changed', $staff, $actor, 'Staff roles changed', ['roles' => $old], ['roles' => $roles->pluck('name')->all()]);
        });
    }

    public function assignRole(Staff $staff, Role $role, Staff $actor): void
    {
        $this->syncRoles($staff, [...$staff->roles()->pluck('id')->all(), $role->id], $actor);
    }
}
