<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return ['supplier_number' => fake()->unique()->bothify('SUP-TEST-########'), 'name' => fake()->company(), 'status' => 'ACTIVE'];
    }
}
