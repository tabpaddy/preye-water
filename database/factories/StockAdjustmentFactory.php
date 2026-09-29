<?php

namespace Database\Factories;

use App\Enums\StockAdjustmentReason;
use App\Enums\StockAdjustmentStatus;
use App\Models\InventoryLocation;
use App\Models\Staff;
use App\Models\StockAdjustment;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockAdjustmentFactory extends Factory
{
    protected $model = StockAdjustment::class;

    public function definition(): array
    {
        return ['adjustment_number' => fake()->unique()->bothify('ADJ-TEST-########'), 'inventory_location_id' => InventoryLocation::factory(), 'reason' => StockAdjustmentReason::PHYSICAL_COUNT, 'status' => StockAdjustmentStatus::DRAFT, 'created_by' => Staff::factory()];
    }
}
