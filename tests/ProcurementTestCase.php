<?php

namespace Tests;

use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\PurchaseOrder;
use App\Models\Staff;
use App\Models\Supplier;
use App\Services\GoodsReceiptService;
use App\Services\ProcurementService;
use App\Services\SupplierInvoiceService;
use App\Services\SupplierPaymentService;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class ProcurementTestCase extends TestCase
{
    use RefreshDatabase;

    protected Staff $actor;

    protected Staff $reviewer;

    protected Supplier $supplier;

    protected InventoryItem $item;

    protected InventoryLocation $location;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actor = Staff::factory()->create();
        $this->actor->assignRole('Super Admin');
        $this->reviewer = Staff::factory()->create();
        $this->reviewer->assignRole('Super Admin');
        $this->supplier = Supplier::factory()->create();
        $this->item = InventoryItem::factory()->create(['item_type' => 'RAW_MATERIAL']);
        $this->location = InventoryLocation::factory()->create();
    }

    protected function orderData(string $quantity = '10', string $cost = '10'): array
    {
        return ['supplier_id' => $this->supplier->id, 'order_date' => '2026-09-29',
            'items' => [['inventory_item_id' => $this->item->id, 'quantity_ordered' => $quantity, 'unit_cost' => $cost]]];
    }

    protected function order(string $quantity = '10', string $cost = '10'): PurchaseOrder
    {
        $service = app(ProcurementService::class);
        $order = $service->create($this->orderData($quantity, $cost), $this->actor);
        $service->submit($order, $this->actor);

        return $service->approve($order, $this->reviewer);
    }

    protected function receipt(PurchaseOrder $order, string $quantity, string $accepted, ?string $cost = null)
    {
        $line = $order->items()->firstOrFail();

        return app(GoodsReceiptService::class)->create([
            'supplier_id' => $order->supplier_id, 'purchase_order_id' => $order->id, 'inventory_location_id' => $this->location->id, 'received_at' => '2026-09-29 10:00:00',
            'items' => [['purchase_order_item_id' => $line->id, 'inventory_item_id' => $line->inventory_item_id,
                'quantity_received' => $quantity, 'quantity_accepted' => $accepted,
                'quantity_rejected' => (string) BigDecimal::of($quantity)->minus($accepted),
                'rejection_reason' => 'Damaged on arrival', 'unit_cost' => $cost ?? $line->unit_cost]],
        ], $this->actor);
    }

    protected function postReceipt($receipt)
    {
        $service = app(GoodsReceiptService::class);
        $service->inspect($receipt, $this->reviewer);

        return $service->post($receipt, $this->actor);
    }

    protected function invoice(string $amount = '100', ?Supplier $supplier = null)
    {
        return app(SupplierInvoiceService::class)->create([
            'supplier_id' => ($supplier ?? $this->supplier)->id, 'invoice_number' => fake()->unique()->bothify('INV-########'), 'invoice_date' => '2026-09-29',
            'items' => [['description' => 'Materials supplied', 'quantity' => '1', 'unit_cost' => $amount]],
        ], $this->actor);
    }

    protected function payment(string $amount = '100')
    {
        $service = app(SupplierPaymentService::class);
        $payment = $service->create(['supplier_id' => $this->supplier->id, 'payment_method' => 'BANK_TRANSFER', 'amount' => $amount], $this->actor);

        return $service->complete($payment,$this->reviewer);
    }
}
