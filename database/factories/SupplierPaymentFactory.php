<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierPaymentFactory extends Factory
{
    protected $model = SupplierPayment::class;

    public function definition(): array
    {
        return ['supplier_id' => Supplier::factory(), 'payment_number' => fake()->unique()->bothify('SPY-TEST-########'), 'payment_method' => 'BANK_TRANSFER', 'amount' => '100.00', 'currency' => 'NGN', 'status' => 'PENDING', 'recorded_by' => Staff::factory()];
    }
}
