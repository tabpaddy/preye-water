<?php

namespace App\Policies;

use App\Models\InventoryStock;
use App\Models\Staff;

class InventoryStockPolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view inventory stocks');
    }

    public function view(Staff $user, InventoryStock $record): bool
    {
        return $user->can('view inventory stocks');
    }

    public function create(Staff $user): bool
    {
        return false;
    }

    public function update(Staff $user, InventoryStock $record): bool
    {
        return false;
    }

    public function delete(Staff $user, InventoryStock $record): bool
    {
        return false;
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }
}
