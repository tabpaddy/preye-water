<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\StaffLeaveRequest;

class StaffLeaveRequestPolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view leave requests');
    }

    public function view(Staff $user, StaffLeaveRequest $record): bool
    {
        return $user->can('view leave requests');
    }

    public function create(Staff $user): bool
    {
        return $user->can('create leave requests');
    }

    public function update(Staff $user, StaffLeaveRequest $record): bool
    {
        return false;
    }

    public function delete(Staff $user, StaffLeaveRequest $record): bool
    {
        return false;
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }
}
