<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\StaffEmploymentDetail;

class StaffEmploymentDetailPolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view staff employment details');
    }

    public function view(Staff $user, StaffEmploymentDetail $record): bool
    {
        return $user->can('view staff employment details');
    }

    public function create(Staff $user): bool
    {
        return $user->can('update staff employment details');
    }

    public function update(Staff $user, StaffEmploymentDetail $record): bool
    {
        return $user->can('update staff employment details');
    }

    public function delete(Staff $user, StaffEmploymentDetail $record): bool
    {
        return false;
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }
}
