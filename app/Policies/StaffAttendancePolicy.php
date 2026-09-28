<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\StaffAttendance;

class StaffAttendancePolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view attendance');
    }

    public function view(Staff $user, StaffAttendance $record): bool
    {
        return $user->can('view attendance');
    }

    public function create(Staff $user): bool
    {
        return $user->can('record attendance');
    }

    public function update(Staff $user, StaffAttendance $record): bool
    {
        return false;
    }

    public function delete(Staff $user, StaffAttendance $record): bool
    {
        return false;
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }
}
