<?php

namespace App\Models;

use App\Enums\GoodsReceiptStatus;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends Model
{
    use HasFactory,HasPublicUuid;

    protected $fillable = ['goods_receipt_id', 'purchase_order_item_id', 'inventory_item_id', 'quantity_received', 'quantity_accepted', 'quantity_rejected', 'unit_cost', 'rejection_reason'];

    protected function casts(): array
    {
        return ['quantity_received' => 'decimal:3', 'quantity_accepted' => 'decimal:3', 'quantity_rejected' => 'decimal:3', 'unit_cost' => 'decimal:4'];
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class)->withTrashed();
    }

    protected static function booted(): void
    {
        $protect = function (self $record): void {
            if ($record->goodsReceipt()->where('status', GoodsReceiptStatus::POSTED)->exists()) {
                throw new \LogicException('Posted receipt quantities cannot be changed.');
            }
        };
        static::updating($protect);
        static::deleting($protect);
    }
}
