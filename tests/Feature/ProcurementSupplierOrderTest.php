<?php

namespace Tests\Feature;

use App\Enums\PurchaseOrderStatus;
use App\Enums\SupplierStatus;
use App\Models\Staff;
use App\Models\SupplierAddress;
use App\Services\ProcurementService;
use App\Services\SupplierService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Tests\ProcurementTestCase;

class ProcurementSupplierOrderTest extends ProcurementTestCase
{
    public function test_supplier_number_cast_and_audit(): void
    {
        $supplier = app(SupplierService::class)->create(['name' => 'Water Materials Ltd', 'status' => 'ACTIVE', 'supplier_type' => 'PACKAGING'], $this->actor);
        $this->assertStringStartsWith('SUP-', $supplier->supplier_number);
        $this->assertSame(SupplierStatus::ACTIVE, $supplier->status);
        $this->assertNotNull($supplier->uuid);
        $this->assertDatabaseHas('activity_logs', ['event' => 'supplier.created']);
    }

    public function test_addresses_keep_one_default(): void
    {
        $service = app(SupplierService::class);
        $data = ['address_line_1' => '1 Road', 'city' => 'Yenagoa', 'state' => 'Bayelsa', 'country' => 'Nigeria', 'is_active' => true, 'is_default' => true];
        $first = $service->saveAddress($this->supplier, $data, $this->actor);
        $second = $service->saveAddress($this->supplier, $data + ['label' => 'Other'], $this->actor);
        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->is_default);
        $this->assertSame(2, $this->supplier->addresses()->count());
    }

    public function test_address_ownership_cannot_change(): void
    {
        $address = SupplierAddress::factory()->create();
        $this->expectException(ValidationException::class);
        app(SupplierService::class)->saveAddress($this->supplier, [], $this->actor, $address);
    }

    public function test_archiving_supplier_keeps_procurement_history(): void
    {
        $order = $this->order();
        app(SupplierService::class)->archive($this->supplier, $this->actor);
        $this->assertSoftDeleted($this->supplier);
        $this->assertSame($this->supplier->id, $order->fresh()->supplier->id);
    }

    public function test_order_totals_and_snapshots_are_authoritative(): void
    {
        $data = $this->orderData('2.125', '10.1234');
        $data['items'][0] += ['discount_amount' => '1.00', 'tax_amount' => '2.00', 'line_total' => '999', 'item_name' => 'Forged', 'sku' => 'Forged'];
        $data += ['subtotal' => '999', 'total_amount' => '999', 'status' => 'APPROVED', 'discount_amount' => '0.50', 'tax_amount' => '1.00', 'other_cost' => '2.00'];
        $order = app(ProcurementService::class)->create($data, $this->actor);
        $this->assertSame('22.51', $order->subtotal);
        $this->assertSame('25.01', $order->total_amount);
        $this->assertSame($this->item->name, $order->items->first()->item_name);
        $this->assertSame($this->item->sku, $order->items->first()->sku);
        $this->assertSame(PurchaseOrderStatus::DRAFT, $order->status);
        $this->assertStringStartsWith('PO-', $order->purchase_order_number);
    }

    public function test_order_approval_has_no_stock_effect(): void
    {
        $order = $this->order();
        $this->assertSame(PurchaseOrderStatus::APPROVED, $order->status);
        $this->assertSame($this->reviewer->id, $order->approved_by);
        $this->assertNotNull($order->approved_at);
        $this->assertDatabaseCount('inventory_stocks', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_only_draft_can_be_edited(): void
    {
        $order = $this->order();
        $this->expectException(ValidationException::class);
        app(ProcurementService::class)->update($order, $this->orderData(), $this->actor);
    }

    public function test_inactive_supplier_rejected_at_creation(): void
    {
        $this->supplier->update(['status' => 'INACTIVE']);
        $this->expectException(ValidationException::class);
        app(ProcurementService::class)->create($this->orderData(), $this->actor);
    }

    public function test_supplier_revalidated_before_approval(): void
    {
        $service = app(ProcurementService::class);
        $order = $service->create($this->orderData(), $this->actor);
        $service->submit($order, $this->actor);
        $this->supplier->update(['status' => 'SUSPENDED']);
        $this->expectException(ValidationException::class);
        $service->approve($order, $this->reviewer);
    }

    public function test_independent_po_approval_required(): void
    {
        $service = app(ProcurementService::class);
        $order = $service->create($this->orderData(), $this->actor);
        $service->submit($order, $this->actor);
        $this->expectException(ValidationException::class);
        $service->approve($order, $this->actor);
    }

    public function test_invalid_transition_rejected(): void
    {
        $order = app(ProcurementService::class)->create($this->orderData(), $this->actor);
        $this->expectException(ValidationException::class);
        app(ProcurementService::class)->approve($order, $this->reviewer);
    }

    public function test_order_cancel_and_close_rules(): void
    {
        $order = $this->order();
        $service = app(ProcurementService::class);
        $this->assertSame(PurchaseOrderStatus::CANCELLED, $service->cancel($order, $this->actor)->status);
        $order = $this->order();
        $this->postReceipt($this->receipt($order, '10', '10'));
        $this->assertSame(PurchaseOrderStatus::CLOSED, $service->close($order, $this->actor)->status);
    }

    public function test_cannot_close_partially_received_order(): void
    {
        $order = $this->order();
        $this->postReceipt($this->receipt($order, '1', '1'));
        $this->expectException(ValidationException::class);
        app(ProcurementService::class)->close($order, $this->actor);
    }

    public function test_unauthorized_supplier_and_po_mutations(): void
    {
        $staff = Staff::factory()->create();
        foreach ([fn () => app(SupplierService::class)->create([], $staff), fn () => app(ProcurementService::class)->approve($this->order(), $staff)] as $operation) {
            try {
                $operation();
                $this->fail();
            } catch (AuthorizationException) {
                $this->assertTrue(true);
            }
        }
    }
}
