<?php

namespace App\Models;

use App\Enums\StockMovementType;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory, HasPublicUuid;

    protected $fillable = ['inventory_item_id', 'from_location_id', 'to_location_id', 'movement_type', 'quantity', 'unit_cost', 'total_cost', 'reference_type', 'reference_id', 'reference_number', 'notes', 'performed_by', 'occurred_at'];

    protected function casts(): array
    {
        return [
            'movement_type' => StockMovementType::class,
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:2',
            'occurred_at' => 'immutable_datetime',
        ];
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class)->withTrashed();
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'to_location_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'performed_by')->withTrashed();
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Posted stock movements are immutable.'));
        static::deleting(fn () => throw new \LogicException('Posted stock movements cannot be deleted.'));
    }
}
