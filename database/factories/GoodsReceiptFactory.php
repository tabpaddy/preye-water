<?php

namespace Database\Factories;

use App\Models\GoodsReceipt;
use App\Models\InventoryLocation;
use App\Models\PurchaseOrder;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

class GoodsReceiptFactory extends Factory
{
    protected $model = GoodsReceipt::class;

    public function definition(): array
    {
        return ['purchase_order_id' => PurchaseOrder::factory()->state(['status' => 'APPROVED']), 'supplier_id' => fn (array $a) => PurchaseOrder::findOrFail($a['purchase_order_id'])->supplier_id, 'goods_receipt_number' => fake()->unique()->bothify('GRN-TEST-########'), 'inventory_location_id' => InventoryLocation::factory(), 'status' => 'DRAFT', 'received_at' => now(), 'received_by' => Staff::factory()];
    }
}
