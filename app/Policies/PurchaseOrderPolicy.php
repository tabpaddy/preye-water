<?php

namespace App\Policies;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\Staff;

class PurchaseOrderPolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view purchase orders');
    }

    public function view(Staff $user, PurchaseOrder $record): bool
    {
        return $user->can('view purchase orders');
    }

    public function create(Staff $user): bool
    {
        return $user->can('create purchase orders');
    }

    public function update(Staff $user, PurchaseOrder $record): bool
    {
        return $user->can('update purchase orders') && $record->status === PurchaseOrderStatus::DRAFT;
    }

    public function delete(Staff $user, PurchaseOrder $record): bool
    {
        return false;
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }
}
