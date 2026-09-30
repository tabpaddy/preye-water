<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItem extends Model
{
    use HasFactory,HasPublicUuid;

    protected $fillable = ['purchase_order_id', 'inventory_item_id', 'item_name', 'sku', 'quantity_ordered', 'quantity_received', 'unit_cost', 'discount_amount', 'tax_amount', 'line_total'];

    protected function casts(): array
    {
        return ['quantity_ordered' => 'decimal:3', 'quantity_received' => 'decimal:3', 'unit_cost' => 'decimal:4', 'discount_amount' => 'decimal:2', 'tax_amount' => 'decimal:2', 'line_total' => 'decimal:2'];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class)->withTrashed();
    }

    public function receiptItems(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function getRemainingQuantityAttribute(): string
    {
        return (string) BigDecimal::of($this->quantity_ordered)->minus($this->quantity_received)->toScale(3);
    }
}
