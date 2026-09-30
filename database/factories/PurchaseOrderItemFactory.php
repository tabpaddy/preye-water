<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderItemFactory extends Factory
{
    protected $model = PurchaseOrderItem::class;

    public function definition(): array
    {
        return ['purchase_order_id' => PurchaseOrder::factory(), 'inventory_item_id' => InventoryItem::factory(), 'item_name' => 'Test material', 'sku' => fake()->bothify('TEST-######'), 'quantity_ordered' => '10.000', 'quantity_received' => '0.000', 'unit_cost' => '10.0000', 'line_total' => '100.00'];
    }
}
