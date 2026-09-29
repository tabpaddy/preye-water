<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryStock;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryStockFactory extends Factory
{
    protected $model = InventoryStock::class;

    public function definition(): array
    {
        return ['inventory_item_id' => InventoryItem::factory(), 'inventory_location_id' => InventoryLocation::factory(), 'quantity_on_hand' => '0.000', 'quantity_reserved' => '0.000'];
    }
}
