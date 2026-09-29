<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        return ['inventory_item_id' => InventoryItem::factory(), 'to_location_id' => InventoryLocation::factory(), 'movement_type' => StockMovementType::ADJUSTMENT_IN, 'quantity' => '1.000', 'occurred_at' => now()];
    }
}
