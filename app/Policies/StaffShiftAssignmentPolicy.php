<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\StaffShiftAssignment;

class StaffShiftAssignmentPolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view shift assignments');
    }

    public function view(Staff $user, StaffShiftAssignment $record): bool
    {
        return $user->can('view shift assignments');
    }

    public function create(Staff $user): bool
    {
        return $user->can('manage shift assignments');
    }

    public function update(Staff $user, StaffShiftAssignment $record): bool
    {
        return $user->can('manage shift assignments');
    }

    public function delete(Staff $user, StaffShiftAssignment $record): bool
    {
        return false;
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }
}
