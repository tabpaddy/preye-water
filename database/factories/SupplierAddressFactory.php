<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\SupplierAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierAddressFactory extends Factory
{
    protected $model = SupplierAddress::class;

    public function definition(): array
    {
        return ['supplier_id' => Supplier::factory(), 'address_line_1' => '1 Factory Road', 'city' => 'Yenagoa', 'state' => 'Bayelsa', 'country' => 'Nigeria', 'is_default' => false, 'is_active' => true];
    }
}
