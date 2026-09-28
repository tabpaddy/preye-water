<?php

namespace App\Policies;

use App\Models\LeaveType;
use App\Models\Staff;

class LeaveTypePolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view leave types');
    }

    public function view(Staff $user, LeaveType $record): bool
    {
        return $user->can('view leave types');
    }

    public function create(Staff $user): bool
    {
        return $user->can('manage leave types');
    }

    public function update(Staff $user, LeaveType $record): bool
    {
        return $user->can('manage leave types');
    }

    public function delete(Staff $user, LeaveType $record): bool
    {
        return false;
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }
}
