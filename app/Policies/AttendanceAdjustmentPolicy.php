<?php

namespace App\Policies;

use App\Models\AttendanceAdjustment;
use App\Models\Staff;

class AttendanceAdjustmentPolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view attendance');
    }

    public function view(Staff $user, AttendanceAdjustment $record): bool
    {
        return $user->can('view attendance');
    }

    public function create(Staff $user): bool
    {
        return false;
    }

    public function update(Staff $user, AttendanceAdjustment $record): bool
    {
        return false;
    }

    public function delete(Staff $user, AttendanceAdjustment $record): bool
    {
        return false;
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }
}
