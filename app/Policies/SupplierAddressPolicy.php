<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\SupplierAddress;

class SupplierAddressPolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view suppliers');
    }

    public function view(Staff $user, SupplierAddress $record): bool
    {
        return $user->can('view suppliers');
    }

    public function create(Staff $user): bool
    {
        return $user->can('update suppliers');
    }

    public function update(Staff $user, SupplierAddress $record): bool
    {
        return $user->can('update suppliers');
    }

    public function delete(Staff $user, SupplierAddress $record): bool
    {
        return false;
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }
}
