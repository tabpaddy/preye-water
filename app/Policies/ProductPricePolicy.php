<?php

namespace App\Policies;

use App\Models\ProductPrice;
use App\Models\Staff;

class ProductPricePolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view product prices');
    }

    public function view(Staff $user, ProductPrice $record): bool
    {
        return $user->can('view product prices');
    }

    public function create(Staff $user): bool
    {
        return $user->can('manage product prices');
    }

    public function update(Staff $user, ProductPrice $record): bool
    {
        return $user->can('manage product prices');
    }

    public function delete(Staff $user, ProductPrice $record): bool
    {
        return false;
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }
}
