<?php

namespace App\Models;

use App\Enums\StockAdjustmentReason;
use App\Enums\StockAdjustmentStatus;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    use HasFactory, HasPublicUuid;

    protected $fillable = ['adjustment_number', 'inventory_location_id', 'reason', 'status', 'notes', 'created_by', 'approved_by', 'approved_at', 'posted_at'];

    protected function casts(): array
    {
        return [
            'reason' => StockAdjustmentReason::class,
            'status' => StockAdjustmentStatus::class,
            'approved_at' => 'immutable_datetime',
            'posted_at' => 'immutable_datetime',
        ];
    }

    public function inventoryLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
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
