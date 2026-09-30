<?php

namespace Tests\Feature;

use App\Enums\SupplierInvoicePaymentStatus;
use App\Enums\SupplierPaymentStatus;
use App\Models\Staff;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierPaymentAllocation;
use App\Services\SupplierInvoiceService;
use App\Services\SupplierPaymentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Tests\ProcurementTestCase;

class ProcurementPaymentsTest extends ProcurementTestCase
{
    public function test_invoice_totals_ignore_client_balance_fields(): void
    {
        $invoice = app(SupplierInvoiceService::class)->create([
            'supplier_id' => $this->supplier->id, 'invoice_number' => 'EXT-001', 'invoice_date' => '2026-09-29',
            'total_amount' => '1', 'amount_paid' => '999', 'amount_due' => '0', 'payment_status' => 'PAID',
            'items' => [['description' => 'Material', 'quantity' => '2.125', 'unit_cost' => '10.1234', 'line_total' => '0']],
        ], $this->actor);
        $this->assertStringStartsWith('SIN-', $invoice->internal_reference);
        $this->assertSame('21.51', $invoice->total_amount);
        $this->assertSame('21.51', $invoice->amount_due);
        $this->assertSame('0.00', $invoice->amount_paid);
        $this->assertSame(SupplierInvoicePaymentStatus::UNPAID, $invoice->payment_status);
    }

    public function test_external_invoice_number_unique_per_supplier(): void
    {
        $data = ['supplier_id' => $this->supplier->id, 'invoice_number' => 'EXT-001', 'invoice_date' => '2026-09-29', 'items' => [['description' => 'Material', 'quantity' => '1', 'unit_cost' => '100']]];
        $service = app(SupplierInvoiceService::class);
        $service->create($data, $this->actor);
        $other = $data;
        $other['supplier_id'] = Supplier::factory()->create()->id;
        $service->create($other, $this->actor);
        $this->assertDatabaseCount('supplier_invoices', 2);
        $this->expectException(ValidationException::class);
        $service->create($data, $this->actor);
    }

    public function test_invoice_can_be_partial_and_po_optional(): void
    {
        $order = $this->order('10', '100000');
        $line = $order->items()->first();
        $invoice = app(SupplierInvoiceService::class)->create([
            'supplier_id' => $this->supplier->id, 'purchase_order_id' => $order->id, 'invoice_number' => 'PARTIAL', 'invoice_date' => '2026-09-29',
            'items' => [['purchase_order_item_id' => $line->id, 'description' => 'First delivery', 'quantity' => '6', 'unit_cost' => '100000']],
        ], $this->actor);
        $this->assertSame('600000.00', $invoice->total_amount);
        $this->assertNull($this->invoice()->purchase_order_id);
    }

    public function test_invoice_supplier_po_mismatch_rejected(): void
    {
        $order = $this->order();
        $this->expectException(ValidationException::class);
        app(SupplierInvoiceService::class)->create([
            'supplier_id' => Supplier::factory()->create()->id, 'purchase_order_id' => $order->id, 'invoice_number' => 'BAD', 'invoice_date' => '2026-09-29',
            'items' => [['description' => 'Material', 'quantity' => '1', 'unit_cost' => '1']],
        ], $this->actor);
    }

    public function test_payment_pending_and_independent_completion(): void
    {
        $service = app(SupplierPaymentService::class);
        $payment = $service->create(['supplier_id' => $this->supplier->id, 'payment_method' => 'CASH', 'amount' => '500', 'status' => 'COMPLETED', 'approved_by' => $this->actor->id], $this->actor);
        $this->assertStringStartsWith('SPY-', $payment->payment_number);
        $this->assertSame(SupplierPaymentStatus::PENDING, $payment->status);
        try {
            $service->complete($payment, $this->actor);
            $this->fail();
        } catch (ValidationException) {
            $this->assertSame(SupplierPaymentStatus::PENDING, $payment->fresh()->status);
        }
        $service->complete($payment, $this->reviewer);
        $this->assertSame($this->reviewer->id, $payment->fresh()->approved_by);
        $this->assertNotNull($payment->fresh()->paid_at);
    }

    public function test_partial_and_full_invoice_payment(): void
    {
        $invoice = $this->invoice('500000');
        $service = app(SupplierPaymentService::class);
        $service->allocate($this->payment('200000'), [['supplier_invoice_id' => $invoice->id, 'amount' => '200000']], $this->actor);
        $this->assertSame('200000.00', $invoice->fresh()->amount_paid);
        $this->assertSame('300000.00', $invoice->fresh()->amount_due);
        $this->assertSame(SupplierInvoicePaymentStatus::PARTIALLY_PAID, $invoice->fresh()->payment_status);
        $service->allocate($this->payment('300000'), [['supplier_invoice_id' => $invoice->id, 'amount' => '300000']], $this->actor);
        $this->assertSame('0.00', $invoice->fresh()->amount_due);
        $this->assertSame(SupplierInvoicePaymentStatus::PAID, $invoice->fresh()->payment_status);
    }

    public function test_multi_invoice_allocation(): void
    {
        $allocations = [];
        foreach (['300000', '150000', '50000'] as $amount) {
            $invoice = $this->invoice($amount);
            $allocations[] = ['supplier_invoice_id' => $invoice->id, 'amount' => $amount];
        }
        $payment = app(SupplierPaymentService::class)->allocate($this->payment('500000'), $allocations, $this->actor);
        $this->assertSame('500000.00', $payment->allocated_amount);
        $this->assertSame('0.00', $payment->unallocated_amount);
        $this->assertSame(3, SupplierInvoice::where('payment_status', 'PAID')->count());
        $this->assertDatabaseCount('supplier_payment_allocations', 3);
    }

    public function test_payment_overallocation_rolls_back_all_lines(): void
    {
        $a = $this->invoice('100000');
        $b = $this->invoice('100000');
        $payment = $this->payment('100000');
        try {
            app(SupplierPaymentService::class)->allocate($payment, [['supplier_invoice_id' => $a->id, 'amount' => '60000'], ['supplier_invoice_id' => $b->id, 'amount' => '60000']], $this->actor);
            $this->fail();
        } catch (ValidationException) {
        }
        $this->assertDatabaseCount('supplier_payment_allocations', 0);
        $this->assertSame('0.00', $a->fresh()->amount_paid);
        $this->assertSame('100000.00', $b->fresh()->amount_due);
    }

    public function test_invoice_overpayment_rejected(): void
    {
        $invoice = $this->invoice('10');
        $payment = $this->payment('20');
        $this->expectException(ValidationException::class);
        app(SupplierPaymentService::class)->allocate($payment, [['supplier_invoice_id' => $invoice->id, 'amount' => '11']], $this->actor);
    }

    public function test_supplier_mismatch_rolls_back_all_allocations(): void
    {
        $a = $this->invoice('50');
        $b = $this->invoice('50', Supplier::factory()->create());
        try {
            app(SupplierPaymentService::class)->allocate($this->payment('100'), [['supplier_invoice_id' => $a->id, 'amount' => '50'], ['supplier_invoice_id' => $b->id, 'amount' => '50']], $this->actor);
            $this->fail();
        } catch (ValidationException) {
        }
        $this->assertDatabaseCount('supplier_payment_allocations', 0);
        $this->assertSame('0.00', $a->fresh()->amount_paid);
    }

    public function test_pending_payment_cannot_allocate(): void
    {
        $payment = app(SupplierPaymentService::class)->create(['supplier_id' => $this->supplier->id, 'payment_method' => 'CASH', 'amount' => '100'], $this->actor);
        $this->expectException(ValidationException::class);
        app(SupplierPaymentService::class)->allocate($payment, [['supplier_invoice_id' => $this->invoice()->id, 'amount' => '1']], $this->actor);
    }

    public function test_duplicate_allocation_cannot_credit_invoice_twice(): void
    {
        $payment = $this->payment('100');
        $invoice = $this->invoice('100');
        $service = app(SupplierPaymentService::class);
        $data = [['supplier_invoice_id' => $invoice->id, 'amount' => '10']];
        $service->allocate($payment, $data, $this->actor);
        try {
            $service->allocate($payment, $data, $this->actor);
            $this->fail();
        } catch (ValidationException) {
        }
        $this->assertSame('10.00', $invoice->fresh()->amount_paid);
        $this->assertDatabaseCount('supplier_payment_allocations', 1);
    }

    public function test_completed_payment_cannot_change_status(): void
    {
        $payment = $this->payment();
        $this->expectException(ValidationException::class);
        app(SupplierPaymentService::class)->cancel($payment, $this->reviewer);
    }

    public function test_allocated_invoice_cannot_be_edited(): void
    {
        $invoice = $this->invoice();
        app(SupplierPaymentService::class)->allocate($this->payment(), [['supplier_invoice_id' => $invoice->id, 'amount' => '1']], $this->actor);
        $data = $invoice->fresh()->toArray();
        $data['invoice_date'] = '2026-09-29';
        $data['items'] = $invoice->items()->get()->toArray();
        $this->expectException(ValidationException::class);
        app(SupplierInvoiceService::class)->update($invoice, $data, $this->actor);
    }

    public function test_invoice_create_and_allocate_authorization(): void
    {
        $staff = Staff::factory()->create();
        foreach ([fn () => app(SupplierInvoiceService::class)->create([], $staff), fn () => app(SupplierPaymentService::class)->allocate($this->payment(), [], $staff)] as $operation) {
            try {
                $operation();
                $this->fail();
            } catch (AuthorizationException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_allocation_failure_rolls_back_snapshots(): void
    {
        $invoice = $this->invoice();
        $payment = $this->payment();
        $dispatcher = SupplierPaymentAllocation::getEventDispatcher();
        SupplierPaymentAllocation::setEventDispatcher(clone $dispatcher);
        SupplierPaymentAllocation::created(fn () => throw new \RuntimeException('forced allocation failure'));
        try {
            try {
                app(SupplierPaymentService::class)->allocate($payment, [['supplier_invoice_id' => $invoice->id, 'amount' => '100']], $this->actor);
                $this->fail();
            } catch (\RuntimeException $e) {
                $this->assertSame('forced allocation failure',$e->getMessage());
            }
            $this->assertDatabaseCount('supplier_payment_allocations',0);
            $this->assertSame('100.00',$invoice->fresh()->amount_due);
        } finally {
            SupplierPaymentAllocation::setEventDispatcher($dispatcher);
        }
    }
}
