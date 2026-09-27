<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\Staff;

class ActivityLogPolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view activity logs');
    }

    public function view(Staff $user, ActivityLog $log): bool
    {
        return $user->can('view activity logs');
    }

    public function create(Staff $user): bool
    {
        return false;
    }

    public function update(Staff $user, ActivityLog $log): bool
    {
        return false;
    }

    public function delete(Staff $user, ActivityLog $log): bool
    {
        return false;
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }
}
