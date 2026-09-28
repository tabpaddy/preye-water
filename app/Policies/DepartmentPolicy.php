<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\Staff;

class DepartmentPolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view departments');
    }

    public function view(Staff $user, Department $record): bool
    {
        return $user->can('view departments');
    }

    public function create(Staff $user): bool
    {
        return $user->can('create departments');
    }

    public function update(Staff $user, Department $record): bool
    {
        return $user->can('update departments');
    }

    public function delete(Staff $user, Department $record): bool
    {
        return false;
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }
}
