<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\Staff;

class ProductPolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view products');
    }

    public function view(Staff $user, Product $record): bool
    {
        return $user->can('view products');
    }

    public function create(Staff $user): bool
    {
        return $user->can('create products');
    }

    public function update(Staff $user, Product $record): bool
    {
        return $user->can('update products');
    }

    public function delete(Staff $user, Product $record): bool
    {
        return false;
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }
}
