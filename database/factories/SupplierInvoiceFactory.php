<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierInvoiceFactory extends Factory
{
    protected $model = SupplierInvoice::class;

    public function definition(): array
    {
        return ['supplier_id' => Supplier::factory(), 'invoice_number' => fake()->unique()->bothify('INV-######'), 'internal_reference' => fake()->unique()->bothify('SIN-TEST-########'), 'invoice_date' => now()->toDateString(), 'subtotal' => '100.00', 'total_amount' => '100.00', 'amount_paid' => '0.00', 'amount_due' => '100.00', 'payment_status' => 'UNPAID', 'created_by' => Staff::factory()];
    }
}
