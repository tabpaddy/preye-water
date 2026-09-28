<?php

namespace App\Policies;

use App\Models\JobPosition;
use App\Models\Staff;

class JobPositionPolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view job positions');
    }

    public function view(Staff $user, JobPosition $record): bool
    {
        return $user->can('view job positions');
    }

    public function create(Staff $user): bool
    {
        return $user->can('create job positions');
    }

    public function update(Staff $user, JobPosition $record): bool
    {
        return $user->can('update job positions');
    }

    public function delete(Staff $user, JobPosition $record): bool
    {
        return false;
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }
}
