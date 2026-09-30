<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\Staff;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return ['purchase_order_number' => fake()->unique()->bothify('PO-TEST-########'), 'supplier_id' => Supplier::factory(), 'status' => 'DRAFT', 'order_date' => now()->toDateString(), 'subtotal' => '100.00', 'total_amount' => '100.00', 'created_by' => Staff::factory()];
    }
}
