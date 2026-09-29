<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockAdjustmentItemFactory extends Factory
{
    protected $model = StockAdjustmentItem::class;

    public function definition(): array
    {
        return ['stock_adjustment_id' => StockAdjustment::factory(), 'inventory_item_id' => InventoryItem::factory(), 'system_quantity' => '0.000', 'counted_quantity' => '1.000', 'difference_quantity' => '1.000'];
    }
}
