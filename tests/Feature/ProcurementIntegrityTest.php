<?php

namespace Tests\Feature;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\SupplierAddress;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use App\Models\SupplierPaymentAllocation;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\ProcurementTestCase;

class ProcurementIntegrityTest extends ProcurementTestCase
{
    public static function uniqueModels(): array
    {
        return [
            [Supplier::class, 'supplier_number'], [Supplier::class, 'uuid'],
            [PurchaseOrder::class, 'purchase_order_number'],
            [GoodsReceipt::class, 'goods_receipt_number'],
            [SupplierInvoice::class, 'internal_reference'],
            [SupplierPayment::class, 'payment_number'],
        ];
    }

    #[DataProvider('uniqueModels')]
    public function test_unique_references_and_uuid(string $model, string $column): void
    {
        $first = $model::factory()->create();
        $second = $model::factory()->make();
        $second->$column = $first->$column;
        $this->expectException(QueryException::class);
        $second->save();
    }

    public function test_one_default_supplier_address_database_constraint(): void
    {
        SupplierAddress::factory()->create(['supplier_id' => $this->supplier->id, 'is_default' => true]);
        $this->expectException(QueryException::class);
        SupplierAddress::factory()->create(['supplier_id' => $this->supplier->id, 'is_default' => true]);
    }

    public function test_external_invoice_pair_unique_in_database(): void
    {
        $invoice = $this->invoice();
        $this->expectException(QueryException::class);
        SupplierInvoice::factory()->create(['supplier_id' => $invoice->supplier_id, 'invoice_number' => $invoice->invoice_number]);
    }

    public function test_allocation_pair_unique_in_database(): void
    {
        $first = SupplierPaymentAllocation::factory()->create();
        $this->expectException(QueryException::class);
        SupplierPaymentAllocation::factory()->create($first->only(['supplier_payment_id', 'supplier_invoice_id']));
    }

    public function test_foreign_keys_preserve_supplier_history(): void
    {
        $this->order();
        $this->expectException(QueryException::class);
        $this->supplier->forceDelete();
    }

    public static function invalidRows(): array
    {
        return [
            [PurchaseOrderItem::class, ['quantity_ordered' => '0']],
            [PurchaseOrderItem::class, ['quantity_received' => '11']],
            [GoodsReceiptItem::class, ['quantity_accepted' => '0.500', 'quantity_rejected' => '0.400']],
            [GoodsReceiptItem::class, ['quantity_rejected' => '-1']],
            [SupplierInvoice::class, ['amount_due' => '101']],
            [SupplierPayment::class, ['amount' => '0']],
            [SupplierPaymentAllocation::class, ['amount' => '0']],
        ];
    }

    #[DataProvider('invalidRows')]
    public function test_database_protects_numeric_invariants(string $model, array $values): void
    {
        $this->expectException(QueryException::class);
        $model::factory()->create($values);
    }

    public function test_completed_payment_model_is_immutable(): void
    {
        $payment = $this->payment();
        $this->expectException(\LogicException::class);
        $payment->update(['amount' => '50']);
    }

    public function test_posted_receipt_model_is_immutable(): void
    {
        $receipt = $this->postReceipt($this->receipt($this->order(), '1', '1'));
        $this->expectException(\LogicException::class);
        $receipt->update(['notes' => 'Changed']);
    }

    public function test_posted_receipt_line_is_immutable(): void
    {
        $receipt = $this->postReceipt($this->receipt($this->order(), '1', '1'));
        $this->expectException(\LogicException::class);
        $receipt->items()->first()->update(['quantity_accepted' => '0']);
    }

    public function test_payment_allocation_is_immutable(): void
    {
        $allocation = SupplierPaymentAllocation::factory()->create();
        $this->expectException(\LogicException::class);
        $allocation->delete();
    }

    public function test_seeding_preserves_custom_manager_grants(): void
    {
        $manager = Role::findByName('Manager', 'staff');
        $manager->givePermissionTo('create suppliers');
        $this->seed();
        $this->assertTrue($manager->fresh()->hasPermissionTo('create suppliers'));
        $this->assertFalse($manager->fresh()->hasPermissionTo('post goods receipts'));
        $this->assertFalse($manager->fresh()->hasPermissionTo('approve supplier payments'));
        $this->assertDatabaseCount('number_sequences', 8);
        $this->assertDatabaseCount('permissions', 89);
    }
}
