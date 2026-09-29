<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\StockMovement;

class StockMovementPolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view stock movements');
    }

    public function view(Staff $user, StockMovement $record): bool
    {
        return $user->can('view stock movements');
    }

    public function create(Staff $user): bool
    {
        return false;
    }

    public function update(Staff $user, StockMovement $record): bool
    {
        return false;
    }

    public function delete(Staff $user, StockMovement $record): bool
    {
        return false;
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }
}
