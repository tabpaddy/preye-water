<?php

namespace Database\Factories;

use App\Enums\InventoryLocationType;
use App\Models\InventoryLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryLocationFactory extends Factory
{
    protected $model = InventoryLocation::class;

    public function definition(): array
    {
        return ['code' => fake()->unique()->bothify('LOC-######'), 'name' => fake()->words(2, true), 'location_type' => InventoryLocationType::WAREHOUSE, 'is_active' => true];
    }
}
