<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\SupplierPayment;

class SupplierPaymentPolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view supplier payments');
    }

    public function view(Staff $user, SupplierPayment $record): bool
    {
        return $user->can('view supplier payments');
    }

    public function create(Staff $user): bool
    {
        return $user->can('create supplier payments');
    }

    public function update(Staff $user, SupplierPayment $record): bool
    {
        return false;
    }

    public function delete(Staff $user, SupplierPayment $record): bool
    {
        return false;
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }
}
