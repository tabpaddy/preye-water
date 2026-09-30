<?php

namespace App\Policies;

use App\Enums\GoodsReceiptStatus;
use App\Models\GoodsReceipt;
use App\Models\Staff;

class GoodsReceiptPolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view goods receipts');
    }

    public function view(Staff $user, GoodsReceipt $record): bool
    {
        return $user->can('view goods receipts');
    }

    public function create(Staff $user): bool
    {
        return $user->can('create goods receipts');
    }

    public function update(Staff $user, GoodsReceipt $record): bool
    {
        return $user->can('update goods receipts') && $record->status === GoodsReceiptStatus::DRAFT;
    }

    public function delete(Staff $user, GoodsReceipt $record): bool
    {
        return false;
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }
}
