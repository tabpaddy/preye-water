<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return ['inventory_item_id' => InventoryItem::factory(), 'name' => fake()->words(3, true), 'slug' => fake()->unique()->slug(), 'is_active' => true, 'is_available_online' => true];
    }
}
