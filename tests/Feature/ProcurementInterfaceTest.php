<?php

namespace Tests\Feature;

use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\GoodsReceipts\Pages\CreateGoodsReceipt;
use App\Filament\Resources\GoodsReceipts\Pages\EditGoodsReceipt;
use App\Filament\Resources\GoodsReceipts\Pages\ListGoodsReceipts;
use App\Filament\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Filament\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Filament\Resources\SupplierInvoices\Pages\CreateSupplierInvoice;
use App\Filament\Resources\SupplierInvoices\Pages\EditSupplierInvoice;
use App\Filament\Resources\SupplierPayments\Pages\CreateSupplierPayment;
use App\Filament\Resources\SupplierPayments\Pages\ListSupplierPayments;
use App\Filament\Resources\SupplierPayments\SupplierPaymentResource;
use App\Filament\Resources\Suppliers\Pages\CreateSupplier;
use App\Filament\Resources\Suppliers\Pages\ViewSupplier;
use App\Filament\Resources\Suppliers\RelationManagers\SupplierAddressesRelationManager;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\Staff;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use App\Services\GoodsReceiptService;
use App\Services\ProcurementService;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\ProcurementTestCase;

class ProcurementInterfaceTest extends ProcurementTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('staff'));
        $this->actingAs($this->actor, 'staff');
    }

    public function test_procurement_lists_and_detail_pages_render(): void
    {
        $order = $this->order();
        $receipt = $this->receipt($order, '1', '1');
        $invoice = $this->invoice();
        $payment = $this->payment();
        foreach (['suppliers' => $this->supplier, 'purchase-orders' => $order, 'goods-receipts' => $receipt, 'supplier-invoices' => $invoice, 'supplier-payments' => $payment] as $slug => $record) {
            $this->get('/staff/'.$slug)->assertOk();
            $this->get('/staff/'.$slug.'/'.$record->uuid)->assertOk();
        }
    }

    public function test_supplier_form_and_address_manager_use_services(): void
    {
        Livewire::test(CreateSupplier::class)->fillForm(['name' => 'Packaging Supplier', 'status' => 'ACTIVE'])->call('create')->assertHasNoFormErrors();
        $supplier = Supplier::where('name', 'Packaging Supplier')->firstOrFail();
        Livewire::test(SupplierAddressesRelationManager::class, ['ownerRecord' => $supplier, 'pageClass' => ViewSupplier::class])
            ->callAction(TestAction::make('create')->table(), ['address_line_1' => '1 Road', 'city' => 'Yenagoa', 'state' => 'Bayelsa', 'country' => 'Nigeria', 'is_default' => true, 'is_active' => true])->assertHasNoActionErrors();
        $this->assertSame(1, $supplier->addresses()->count());
        $this->assertDatabaseHas('activity_logs', ['event' => 'supplier.address_saved']);
    }

    public function test_po_create_edit_and_approval_ui(): void
    {
        Livewire::test(CreatePurchaseOrder::class)->fillForm($this->orderData('2.125', '10.1234'))->call('create')->assertHasNoFormErrors();
        $order = PurchaseOrder::firstOrFail();
        $this->assertSame('21.51', $order->total_amount);
        Livewire::test(EditPurchaseOrder::class, ['record' => $order->uuid])->fillForm(['notes' => 'Ready for review'])->call('save')->assertHasNoFormErrors();
        Livewire::test(ListPurchaseOrders::class)->callAction(TestAction::make('submit')->table($order))->assertHasNoActionErrors();
        $this->flushSession();
        $this->actingAs($this->reviewer, 'staff');
        Livewire::test(ListPurchaseOrders::class)->callAction(TestAction::make('approve')->table($order))->assertHasNoActionErrors();
        $this->assertSame(PurchaseOrderStatus::APPROVED, $order->fresh()->status);
        Livewire::test(EditPurchaseOrder::class, ['record' => $order->uuid])->assertForbidden();
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_goods_receipt_create_inspect_and_post_ui(): void
    {
        $order = $this->order();
        $line = $order->items()->first();
        Livewire::test(CreateGoodsReceipt::class)->fillForm([
            'supplier_id' => $this->supplier->id, 'purchase_order_id' => $order->id, 'inventory_location_id' => $this->location->id,
            'received_at' => '2026-09-29 11:00:00', 'items' => [['purchase_order_item_id' => $line->id, 'quantity_received' => '2.125', 'quantity_accepted' => '2.000', 'quantity_rejected' => '0.125', 'unit_cost' => '10', 'rejection_reason' => 'Damaged']],
        ])->call('create')->assertHasNoFormErrors();
        $receipt = GoodsReceipt::firstOrFail();
        $page = Livewire::test(EditGoodsReceipt::class, ['record' => $receipt->uuid]);
        $this->assertSame('10.000', array_values($page->get('data.items'))[0]['ordered']);
        $page->call('save')->assertHasNoFormErrors();
        Livewire::test(ListGoodsReceipts::class)->callAction(TestAction::make('inspect')->table($receipt))->assertHasNoActionErrors();
        Livewire::test(ListGoodsReceipts::class)->callAction(TestAction::make('post')->table($receipt))->assertHasNoActionErrors();
        $this->assertSame(GoodsReceiptStatus::POSTED, $receipt->fresh()->status);
        $this->assertDatabaseHas('stock_movements', ['reference_id' => $receipt->id, 'quantity' => '2.000', 'movement_type' => 'SUPPLIER_RECEIPT_IN']);
        Livewire::test(EditGoodsReceipt::class, ['record' => $receipt->uuid])->assertForbidden();
    }

    public function test_invoice_and_payment_forms_and_allocation_action(): void
    {
        Livewire::test(CreateSupplierInvoice::class)->fillForm([
            'supplier_id' => $this->supplier->id, 'invoice_number' => 'UI-INV', 'invoice_date' => '2026-09-29',
            'items' => [['description' => 'Packaging', 'quantity' => '1', 'unit_cost' => '100']],
        ])->call('create')->assertHasNoFormErrors();
        $invoice = SupplierInvoice::firstOrFail();
        Livewire::test(CreateSupplierPayment::class)->fillForm([
            'supplier_id' => $this->supplier->id, 'payment_method' => 'CASH', 'amount' => '25.25', 'currency' => 'NGN',
        ])->call('create')->assertHasNoFormErrors();
        $payment = SupplierPayment::firstOrFail();
        $this->flushSession();
        $this->actingAs($this->reviewer, 'staff');
        Livewire::test(ListSupplierPayments::class)->callAction(TestAction::make('complete')->table($payment))->assertHasNoActionErrors();
        Livewire::test(ListSupplierPayments::class)->callAction(TestAction::make('allocate')->table($payment), ['allocations' => [['supplier_invoice_id' => $invoice->id, 'amount' => '25.25']]])->assertHasNoActionErrors();
        $this->assertSame('74.75', $invoice->fresh()->amount_due);
        $this->assertArrayNotHasKey('edit', SupplierPaymentResource::getPages());
        Livewire::test(EditSupplierInvoice::class, ['record' => $invoice->uuid])->assertForbidden();
    }

    public function test_invoice_balance_fields_are_not_editable_components(): void
    {
        $page = Livewire::test(CreateSupplierInvoice::class);
        foreach (['amount_paid', 'amount_due', 'payment_status', 'total_amount'] as $field) {
            $page->assertFormFieldDoesNotExist($field);
        }
    }

    public function test_read_only_staff_cannot_invoke_sensitive_actions(): void
    {
        $order = app(ProcurementService::class)->create($this->orderData(), $this->actor);
        app(ProcurementService::class)->submit($order, $this->actor);
        $receipt = $this->receipt($this->order(), '1', '1');
        app(GoodsReceiptService::class)->inspect($receipt, $this->reviewer);
        $payment = $this->payment();
        $viewer = Staff::factory()->create();
        $viewer->assignRole('Manager');
        $this->flushSession();
        $this->actingAs($viewer, 'staff');
        Livewire::test(ListPurchaseOrders::class)->assertActionHidden(TestAction::make('approve')->table($order))->mountAction(TestAction::make('approve')->table($order))->callMountedAction();
        Livewire::test(ListGoodsReceipts::class)->assertActionHidden(TestAction::make('post')->table($receipt))->mountAction(TestAction::make('post')->table($receipt))->callMountedAction();
        Livewire::test(ListSupplierPayments::class)->assertActionHidden(TestAction::make('allocate')->table($payment));
        $this->assertSame(PurchaseOrderStatus::PENDING_APPROVAL, $order->fresh()->status);
        $this->assertSame(GoodsReceiptStatus::INSPECTED, $receipt->fresh()->status);
        Livewire::test(CreateSupplier::class)->assertForbidden();
    }

    public function test_staff_without_procurement_access_is_denied(): void
    {
        $this->flushSession();
        $this->actingAs(Staff::factory()->create(), 'staff');
        foreach (['suppliers', 'purchase-orders', 'goods-receipts', 'supplier-invoices', 'supplier-payments'] as $slug) {
            $this->get('/staff/'.$slug)->assertForbidden();
        }
    }
}
