<?php

namespace App\Models;

use App\Enums\GoodsReceiptStatus;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    use HasFactory,HasPublicUuid;

    protected $fillable = ['goods_receipt_number', 'purchase_order_id', 'supplier_id', 'inventory_location_id', 'supplier_delivery_note', 'status', 'received_at', 'received_by', 'inspected_by', 'posted_by', 'posted_at', 'notes'];

    protected function casts(): array
    {
        return ['status' => GoodsReceiptStatus::class, 'received_at' => 'immutable_datetime', 'posted_at' => 'immutable_datetime'];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function inventoryLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'received_by')->withTrashed();
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'inspected_by')->withTrashed();
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'posted_by')->withTrashed();
    }

    protected static function booted(): void
    {
        static::updating(function (self $record): void {
            if ($record->getRawOriginal('status') === GoodsReceiptStatus::POSTED->value) {
                throw new \LogicException('Posted goods receipts are immutable.');
            }
        });
        static::deleting(function (self $record): void {
            if ($record->status === GoodsReceiptStatus::POSTED) {
                throw new \LogicException('Posted goods receipts cannot be deleted.');
            }
        });
    }
}
