<?php

namespace App\Policies;

use App\Models\Staff;

class StaffPolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view staff');
    }

    public function view(Staff $user, Staff $staff): bool
    {
        return $user->can('view staff');
    }

    public function create(Staff $user): bool
    {
        return $user->can('create staff');
    }

    public function update(Staff $user, Staff $staff): bool
    {
        return $user->can('update staff');
    }

    public function delete(Staff $user, Staff $staff): bool
    {
        return $user->id !== $staff->id && $user->can('delete staff');
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }

    public function forceDelete(Staff $user, Staff $staff): bool
    {
        return false;
    }
}
