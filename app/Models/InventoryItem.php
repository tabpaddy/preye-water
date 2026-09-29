<?php

namespace App\Models;

use App\Enums\InventoryItemType;
use App\Enums\UnitOfMeasure;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory, HasPublicUuid, SoftDeletes;

    protected $fillable = ['sku', 'name', 'description', 'item_type', 'unit_of_measure', 'reorder_level', 'is_stock_tracked', 'is_active'];

    protected function casts(): array
    {
        return [
            'item_type' => InventoryItemType::class,
            'unit_of_measure' => UnitOfMeasure::class,
            'reorder_level' => 'decimal:3',
            'is_stock_tracked' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function product(): HasOne
    {
        return $this->hasOne(Product::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function adjustmentItems(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeStockTracked($query)
    {
        return $query->where('is_stock_tracked', true);
    }

    public function scopeFinishedGoods($query)
    {
        return $query->where('item_type', InventoryItemType::FINISHED_GOOD);
    }

    public function scopeRawMaterials($query)
    {
        return $query->where('item_type', InventoryItemType::RAW_MATERIAL);
    }

    public function scopeLowStock($query)
    {
        return $query->active()->stockTracked()->whereNotNull('reorder_level')->whereRaw('COALESCE((SELECT SUM(quantity_on_hand - quantity_reserved) FROM inventory_stocks JOIN inventory_locations ON inventory_locations.id = inventory_stocks.inventory_location_id WHERE inventory_stocks.inventory_item_id = inventory_items.id AND inventory_locations.is_active = 1), 0) <= inventory_items.reorder_level');
    }
}
