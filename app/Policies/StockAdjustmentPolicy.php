<?php

namespace App\Policies;

use App\Enums\StockAdjustmentStatus;
use App\Models\Staff;
use App\Models\StockAdjustment;

class StockAdjustmentPolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view stock adjustments');
    }

    public function view(Staff $user, StockAdjustment $record): bool
    {
        return $user->can('view stock adjustments');
    }

    public function create(Staff $user): bool
    {
        return $user->can('create stock adjustments');
    }

    public function update(Staff $user, StockAdjustment $record): bool
    {
        return $user->can('update stock adjustments') && $record->status === StockAdjustmentStatus::DRAFT;
    }

    public function delete(Staff $user, StockAdjustment $record): bool
    {
        return false;
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }
}
