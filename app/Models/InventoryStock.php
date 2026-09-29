<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStock extends Model
{
    use HasFactory;

    protected $fillable = ['inventory_item_id', 'inventory_location_id', 'quantity_on_hand', 'quantity_reserved', 'average_unit_cost'];

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'decimal:3',
            'quantity_reserved' => 'decimal:3',
            'average_unit_cost' => 'decimal:4',
        ];
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class)->withTrashed();
    }

    public function inventoryLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    public const CREATED_AT = null;

    public function getAvailableQuantityAttribute(): string
    {
        return (string) BigDecimal::of($this->quantity_on_hand)->minus($this->quantity_reserved)->toScale(3);
    }

    public function getEstimatedValueAttribute(): ?string
    {
        return $this->average_unit_cost === null ? null : (string) BigDecimal::of($this->quantity_on_hand)->multipliedBy($this->average_unit_cost)->toScale(2, RoundingMode::HALF_UP);
    }
}
