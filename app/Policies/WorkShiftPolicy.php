<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\WorkShift;

class WorkShiftPolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view work shifts');
    }

    public function view(Staff $user, WorkShift $record): bool
    {
        return $user->can('view work shifts');
    }

    public function create(Staff $user): bool
    {
        return $user->can('create work shifts');
    }

    public function update(Staff $user, WorkShift $record): bool
    {
        return $user->can('update work shifts');
    }

    public function delete(Staff $user, WorkShift $record): bool
    {
        return false;
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }
}
