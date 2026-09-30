<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\SupplierInvoice;

class SupplierInvoicePolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view supplier invoices');
    }

    public function view(Staff $user, SupplierInvoice $record): bool
    {
        return $user->can('view supplier invoices');
    }

    public function create(Staff $user): bool
    {
        return $user->can('create supplier invoices');
    }

    public function update(Staff $user, SupplierInvoice $record): bool
    {
        return $user->can('update supplier invoices') && ! ($record->relationLoaded('allocations') ? $record->allocations->isNotEmpty() : $record->allocations()->exists());
    }

    public function delete(Staff $user, SupplierInvoice $record): bool
    {
        return false;
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }
}
