<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory,HasPublicUuid;

    protected $fillable = ['purchase_order_number', 'supplier_id', 'status', 'order_date', 'expected_delivery_date', 'subtotal', 'discount_amount', 'tax_amount', 'other_cost', 'total_amount', 'supplier_reference', 'notes', 'created_by', 'approved_by', 'approved_at'];

    protected function casts(): array
    {
        return ['status' => PurchaseOrderStatus::class, 'order_date' => 'date', 'expected_delivery_date' => 'date', 'approved_at' => 'immutable_datetime', 'subtotal' => 'decimal:2', 'discount_amount' => 'decimal:2', 'tax_amount' => 'decimal:2', 'other_cost' => 'decimal:2', 'total_amount' => 'decimal:2'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SupplierInvoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by')->withTrashed();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by')->withTrashed();
    }
}
