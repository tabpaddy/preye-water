<?php

namespace App\Policies;

use App\Models\InventoryLocation;
use App\Models\Staff;

class InventoryLocationPolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view inventory locations');
    }

    public function view(Staff $user, InventoryLocation $record): bool
    {
        return $user->can('view inventory locations');
    }

    public function create(Staff $user): bool
    {
        return $user->can('create inventory locations');
    }

    public function update(Staff $user, InventoryLocation $record): bool
    {
        return $user->can('update inventory locations');
    }

    public function delete(Staff $user, InventoryLocation $record): bool
    {
        return false;
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }
}
