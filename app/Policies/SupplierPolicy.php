<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\Supplier;

class SupplierPolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view suppliers');
    }

    public function view(Staff $user, Supplier $record): bool
    {
        return $user->can('view suppliers');
    }

    public function create(Staff $user): bool
    {
        return $user->can('create suppliers');
    }

    public function update(Staff $user, Supplier $record): bool
    {
        return $user->can('update suppliers');
    }

    public function delete(Staff $user, Supplier $record): bool
    {
        return false;
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }
}
