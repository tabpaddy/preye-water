<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\Staff;

class InventoryItemPolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view inventory items');
    }

    public function view(Staff $user, InventoryItem $record): bool
    {
        return $user->can('view inventory items');
    }

    public function create(Staff $user): bool
    {
        return $user->can('create inventory items');
    }

    public function update(Staff $user, InventoryItem $record): bool
    {
        return $user->can('update inventory items');
    }

    public function delete(Staff $user, InventoryItem $record): bool
    {
        return false;
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }
}
