<?php

namespace Database\Factories;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class GoodsReceiptItemFactory extends Factory
{
    protected $model = GoodsReceiptItem::class;

    public function definition(): array
    {
        return ['goods_receipt_id' => GoodsReceipt::factory(), 'purchase_order_item_id' => fn (array $a) => PurchaseOrderItem::factory()->create(['purchase_order_id' => GoodsReceipt::findOrFail($a['goods_receipt_id'])->purchase_order_id])->id, 'inventory_item_id' => fn (array $a) => PurchaseOrderItem::findOrFail($a['purchase_order_item_id'])->inventory_item_id, 'quantity_received' => '1.000', 'quantity_accepted' => '1.000', 'quantity_rejected' => '0.000', 'unit_cost' => '10.0000'];
    }
}
