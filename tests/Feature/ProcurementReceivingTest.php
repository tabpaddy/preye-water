<?php

namespace Tests\Feature;

use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Models\GoodsReceipt;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Staff;
use App\Models\StockMovement;
use App\Services\GoodsReceiptService;
use App\Services\InventoryService;
use App\Services\ProcurementService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Tests\ProcurementTestCase;

class ProcurementReceivingTest extends ProcurementTestCase
{
    public function test_partial_receiving_then_full_receiving(): void
    {
        $order = $this->order('10000');
        $first = $this->postReceipt($this->receipt($order, '6000', '6000'));
        $this->assertSame(PurchaseOrderStatus::PARTIALLY_RECEIVED, $order->fresh()->status);
        $this->assertSame('6000.000', $order->items()->first()->quantity_received);
        $this->postReceipt($this->receipt($order, '4000', '4000'));
        $this->assertSame(PurchaseOrderStatus::RECEIVED, $order->fresh()->status);
        $this->assertSame('10000.000', $order->items()->first()->quantity_received);
        $this->assertSame('10000.000', InventoryStock::first()->quantity_on_hand);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertSame(GoodsReceiptStatus::POSTED, $first->status);
    }

    public function test_rejected_units_never_enter_stock(): void
    {
        $order = $this->order('1000');
        $receipt = $this->postReceipt($this->receipt($order, '1000', '950'));
        $this->assertSame('950.000', InventoryStock::first()->quantity_on_hand);
        $this->assertSame('950.000', $order->items()->first()->quantity_received);
        $this->assertSame('50.000', $receipt->items()->first()->quantity_rejected);
        $this->assertDatabaseCount('inventory_stocks', 1);
    }

    public function test_receipt_costing_reuses_inventory_and_has_grn_reference(): void
    {
        app(InventoryService::class)->receive($this->item, $this->location, '100', '20', $this->actor);
        $receipt = $this->postReceipt($this->receipt($this->order('100', '30'), '100', '100'));
        $stock = InventoryStock::first();
        $movement = StockMovement::latest('id')->first();
        $this->assertSame('200.000', $stock->quantity_on_hand);
        $this->assertSame('25.0000', $stock->average_unit_cost);
        $this->assertSame(StockMovementType::SUPPLIER_RECEIPT_IN, $movement->movement_type);
        $this->assertSame('30.0000', $movement->unit_cost);
        $this->assertSame('3000.00', $movement->total_cost);
        $this->assertSame(GoodsReceipt::class, $movement->reference_type);
        $this->assertSame($receipt->id, $movement->reference_id);
        $this->assertSame($receipt->goods_receipt_number, $movement->reference_number);
        $this->assertSame($this->actor->id, $movement->performed_by);
        $this->assertTrue($movement->occurred_at->equalTo($receipt->received_at));
    }

    public function test_competing_receipt_is_revalidated_at_post(): void
    {
        $order = $this->order('10000');
        $stale = $this->receipt($order, '3000', '3000');
        $service = app(GoodsReceiptService::class);
        $service->inspect($stale, $this->reviewer);
        $this->postReceipt($this->receipt($order, '8000', '8000'));
        try {
            $service->post($stale, $this->actor);
            $this->fail();
        } catch (ValidationException) {
        }
        $this->assertSame('8000.000', InventoryStock::first()->quantity_on_hand);
        $this->assertSame('8000.000', $order->items()->first()->quantity_received);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertSame(GoodsReceiptStatus::INSPECTED, $stale->fresh()->status);
    }

    public function test_draft_and_inspection_do_not_add_stock(): void
    {
        $order = $this->order();
        $receipt = $this->receipt($order, '2', '2');
        app(GoodsReceiptService::class)->inspect($receipt, $this->reviewer);
        $this->assertDatabaseCount('inventory_stocks', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame('0.000', $order->items()->first()->quantity_received);
    }

    public function test_posting_requires_inspection(): void
    {
        $this->expectException(ValidationException::class);
        app(GoodsReceiptService::class)->post($this->receipt($this->order(), '1', '1'), $this->actor);
    }

    public function test_posted_receipt_cannot_edit_or_post_twice(): void
    {
        $receipt = $this->postReceipt($this->receipt($this->order(), '1', '1'));
        $service = app(GoodsReceiptService::class);
        try {
            $service->post($receipt, $this->actor);
            $this->fail();
        } catch (ValidationException) {
            $this->assertDatabaseCount('stock_movements', 1);
        }
        $data = $receipt->toArray();
        $data['items'] = $receipt->items()->get()->toArray();
        $this->expectException(ValidationException::class);
        $service->update($receipt, $data, $this->actor);
    }

    public function test_invalid_inspection_split_rejected(): void
    {
        $receipt = $this->receipt($this->order(), '1', '1');
        $data = $receipt->toArray();
        $data['items'] = $receipt->items->toArray();
        $data['items'][0]['quantity_rejected'] = '1';
        $this->expectException(ValidationException::class);
        app(GoodsReceiptService::class)->update($receipt, $data, $this->actor);
    }

    public function test_item_substitution_rejected(): void
    {
        $receipt = $this->receipt($this->order(), '1', '1');
        $data = $receipt->toArray();
        $data['items'] = $receipt->items->toArray();
        $data['items'][0]['inventory_item_id'] = InventoryItem::factory()->create()->id;
        $this->expectException(ValidationException::class);
        app(GoodsReceiptService::class)->update($receipt, $data, $this->actor);
    }

    public function test_post_requires_domain_permission_but_not_operate_inventory(): void
    {
        $receipt = $this->receipt($this->order(), '1', '1');
        app(GoodsReceiptService::class)->inspect($receipt, $this->reviewer);
        $poster = Staff::factory()->create();
        try {
            app(GoodsReceiptService::class)->post($receipt, $poster);
            $this->fail();
        } catch (AuthorizationException) {
        }
        $poster->givePermissionTo('post goods receipts');
        app(GoodsReceiptService::class)->post($receipt, $poster);
        $this->assertFalse($poster->can('operate inventory'));
        $this->assertSame($poster->id, $receipt->fresh()->posted_by);
    }

    public function test_multi_item_failure_rolls_back_stock_po_and_receipt(): void
    {
        $second = InventoryItem::factory()->create();
        $data = $this->orderData();
        $data['items'][] = ['inventory_item_id' => $second->id, 'quantity_ordered' => '10', 'unit_cost' => '20'];
        $po = app(ProcurementService::class)->create($data, $this->actor);
        app(ProcurementService::class)->submit($po, $this->actor);
        app(ProcurementService::class)->approve($po, $this->reviewer);
        $lines = $po->items()->get()->map(fn ($line) => ['purchase_order_item_id' => $line->id, 'quantity_received' => '1', 'quantity_accepted' => '1', 'quantity_rejected' => '0', 'unit_cost' => $line->unit_cost])->all();
        $service = app(GoodsReceiptService::class);
        $receipt = $service->create(['supplier_id' => $this->supplier->id, 'purchase_order_id' => $po->id, 'inventory_location_id' => $this->location->id, 'received_at' => now(), 'items' => $lines], $this->actor);
        $service->inspect($receipt, $this->reviewer);
        $dispatcher = StockMovement::getEventDispatcher();
        StockMovement::setEventDispatcher(clone $dispatcher);
        $count = 0;
        StockMovement::creating(function () use (&$count) {
            if (++$count === 2) {
                throw new \RuntimeException('forced failure');
            }
        });
        try {
            try {
                $service->post($receipt, $this->actor);
                $this->fail();
            } catch (\RuntimeException $e) {
                $this->assertSame('forced failure', $e->getMessage());
            }
            $this->assertDatabaseCount('inventory_stocks', 0);
            $this->assertDatabaseCount('stock_movements', 0);
            $this->assertSame('0.000', $po->items()->first()->quantity_received);
            $this->assertSame(GoodsReceiptStatus::INSPECTED, $receipt->fresh()->status);
        } finally {
            StockMovement::setEventDispatcher($dispatcher);
        }
    }

    public function test_direct_receipt_is_not_supported(): void
    {
        $this->expectException(ValidationException::class);
        app(GoodsReceiptService::class)->create(['supplier_id' => $this->supplier->id, 'inventory_location_id' => $this->location->id, 'received_at' => now(), 'items' => []], $this->actor);
    }

    public function test_inactive_location_blocks_posting(): void
    {
        $receipt = $this->receipt($this->order(), '1', '1');
        app(GoodsReceiptService::class)->inspect($receipt, $this->reviewer);
        $this->location->update(['is_active' => false]);
        $this->expectException(ValidationException::class);
        app(GoodsReceiptService::class)->post($receipt, $this->actor);
    }

    public function test_reject_and_cancel_do_not_move_stock(): void
    {
        $service = app(GoodsReceiptService::class);
        $order = $this->order();
        $this->assertSame(GoodsReceiptStatus::REJECTED,$service->reject($this->receipt($order,'1','1'),$this->reviewer,'Wrong packaging')->status);
        $this->assertSame(GoodsReceiptStatus::CANCELLED,$service->cancel($this->receipt($order,'1','1'),$this->actor)->status);
        $this->assertDatabaseCount('stock_movements',0);
    }
}
