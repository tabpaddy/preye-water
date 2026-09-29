<?php

namespace Database\Factories;

use App\Enums\InventoryItemType;
use App\Enums\UnitOfMeasure;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    public function definition(): array
    {
        return ['sku' => fake()->unique()->bothify('SKU-########'), 'name' => fake()->words(3, true), 'item_type' => InventoryItemType::FINISHED_GOOD, 'unit_of_measure' => UnitOfMeasure::PACK, 'is_active' => true, 'is_stock_tracked' => true];
    }
}
