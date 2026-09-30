<?php

namespace App\Services;

use App\Enums\SupplierInvoicePaymentStatus as PaymentStatus;
use App\Enums\SupplierPaymentStatus;
use App\Models\PurchaseOrder;
use App\Models\Staff;
use App\Models\SupplierInvoice;
use App\Support\InventoryDecimal;
use App\Support\ProcurementTotals;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupplierInvoiceService
{
    public function __construct(private SupplierService $suppliers, private NumberSequenceService $numbers, private ActivityLogService $audit) {}

    public function create(array $data, Staff $actor): SupplierInvoice
    {
        Gate::forUser($actor)->authorize('create supplier invoices');

        return $this->save(new SupplierInvoice, $data, $actor);
    }

    public function update(SupplierInvoice $invoice, array $data, Staff $actor): SupplierInvoice
    {
        Gate::forUser($actor)->authorize('update supplier invoices');

        return $this->save($invoice, $data, $actor);
    }

    private function save(SupplierInvoice $invoice, array $data, Staff $actor): SupplierInvoice
    {
        return DB::transaction(function () use ($invoice, $data, $actor) {
            $data = Validator::make($data, [
                'supplier_id' => ['required', 'integer', 'exists:suppliers,id'], 'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
                'invoice_number' => ['required', 'string', 'max:255'], 'invoice_date' => ['required', 'date_format:Y-m-d'],
                'due_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:invoice_date'], 'notes' => ['nullable', 'string', 'max:5000'],
                'discount_amount' => ['sometimes'], 'tax_amount' => ['sometimes'], 'other_amount' => ['sometimes'],
                'items' => ['required', 'array', 'min:1', 'max:200'],
                'items.*.purchase_order_item_id' => ['nullable', 'integer', 'exists:purchase_order_items,id'],
                'items.*.inventory_item_id' => ['nullable', 'integer', 'exists:inventory_items,id'],
                'items.*.description' => ['required', 'string', 'max:5000'], 'items.*.quantity' => ['required'], 'items.*.unit_cost' => ['required'],
                'items.*.discount_amount' => ['sometimes'], 'items.*.tax_amount' => ['sometimes'],
            ])->validate();
            if ($invoice->exists && (int) $data['supplier_id'] !== $invoice->supplier_id) {
                throw ValidationException::withMessages(['supplier_id' => 'The invoice supplier cannot be changed.']);
            }
            $this->suppliers->lockActive((int) $data['supplier_id']);
            $order = empty($data['purchase_order_id']) ? null : PurchaseOrder::whereKey($data['purchase_order_id'])->lockForUpdate()->firstOrFail();
            if ($order && $order->supplier_id !== (int) $data['supplier_id']) {
                throw ValidationException::withMessages(['purchase_order_id' => 'PO belongs to another supplier.']);
            }
            if ($invoice->exists) {
                $invoice = SupplierInvoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
                if ($invoice->allocations()->exists()) {
                    throw ValidationException::withMessages(['invoice' => 'An allocated invoice cannot be edited.']);
                }
            }
            Validator::make($data, ['invoice_number' => [Rule::unique('supplier_invoices', 'invoice_number')->where('supplier_id', $data['supplier_id'])->ignore($invoice->id)]])->validate();
            $poItems = $order?->items()->get()->keyBy('id');
            $lines = [];
            foreach ($data['items'] as $input) {
                $poLine = empty($input['purchase_order_item_id']) ? null : $poItems?->get($input['purchase_order_item_id']);
                if (! empty($input['purchase_order_item_id']) && ! $poLine) {
                    throw ValidationException::withMessages(['items' => 'Invoice line does not belong to the selected PO.']);
                }
                if ($poLine && ! empty($input['inventory_item_id']) && (int) $input['inventory_item_id'] !== $poLine->inventory_item_id) {
                    throw ValidationException::withMessages(['items' => 'Invoice inventory item does not match the PO line.']);
                }
                $itemId = $poLine?->inventory_item_id ?? ($input['inventory_item_id'] ?? null);
                $lines[] = ProcurementTotals::line($input, 'quantity') + ['purchase_order_item_id' => $poLine?->id, 'inventory_item_id' => $itemId, 'description' => $input['description']];
            }
            $totals = ProcurementTotals::document($lines, $data, 'other_amount', true);
            unset($data['items']);
            $event = $invoice->exists ? 'updated' : 'created';
            $old = $invoice->only(['invoice_number', 'total_amount']);
            if (! $invoice->exists) {
                $invoice->fill(['internal_reference' => $this->numbers->next('SUPPLIER_INVOICE'), 'created_by' => $actor->id]);
            }
            $invoice->fill(array_merge($data, $totals, ['amount_paid' => '0.00', 'amount_due' => $totals['total_amount'], 'payment_status' => PaymentStatus::UNPAID]))->save();
            $invoice->items()->delete();
            $invoice->items()->createMany($lines);
            $this->audit->record('supplier_invoice.'.$event, $invoice, $actor, 'Supplier invoice '.$event, $old, $invoice->only(['supplier_id', 'invoice_number', 'total_amount']));

            return $invoice->load('items');
        }, 5);
    }

    /** Called under the payment workflow's locked supplier/invoice transaction. */
    public function refreshPaymentBalance(SupplierInvoice $invoice): SupplierInvoice
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Invoice balance refresh requires a transaction.');
        }
        $invoice = SupplierInvoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
        $paid = BigDecimal::zero();
        foreach ($invoice->allocations()->whereHas('supplierPayment', fn ($q) => $q->where('status', SupplierPaymentStatus::COMPLETED))->get() as $allocation) {
            $paid = $paid->plus($allocation->amount);
        }
        $due = BigDecimal::of($invoice->total_amount)->minus($paid);
        if ($due->isNegative()) {
            throw ValidationException::withMessages(['allocations' => 'Invoice allocation exceeds its total.']);
        }
        $status = $paid->isZero() ? PaymentStatus::UNPAID : ($due->isZero() ? PaymentStatus::PAID : PaymentStatus::PARTIALLY_PAID);
        $invoice->update(['amount_paid' => InventoryDecimal::value((string) $paid, 2), 'amount_due' => InventoryDecimal::value((string) $due,2), 'payment_status' => $status]);

        return $invoice;
    }
}
