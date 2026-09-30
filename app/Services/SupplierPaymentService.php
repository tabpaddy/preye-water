<?php

namespace App\Services;

use App\Enums\SupplierPaymentMethod;
use App\Enums\SupplierPaymentStatus as Status;
use App\Models\Staff;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use App\Support\InventoryDecimal as Decimal;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupplierPaymentService
{
    public function __construct(private SupplierInvoiceService $invoices, private NumberSequenceService $numbers, private ActivityLogService $audit) {}

    public function create(array $data, Staff $actor): SupplierPayment
    {
        Gate::forUser($actor)->authorize('create supplier payments');
        $data = Validator::make($data, [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'], 'payment_method' => ['required', Rule::enum(SupplierPaymentMethod::class)],
            'amount' => ['required'], 'currency' => ['sometimes', Rule::in(['NGN'])], 'reference' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['nullable', 'date', 'before_or_equal:now'], 'notes' => ['nullable', 'string', 'max:5000'],
        ])->validate();
        $data['amount'] = Decimal::value($data['amount'], 2, true, 'amount');

        return DB::transaction(function () use ($data, $actor) {
            // Existing obligations may still be settled for inactive/archived suppliers.
            Supplier::withTrashed()->whereKey($data['supplier_id'])->lockForUpdate()->firstOrFail();
            $payment = SupplierPayment::create($data + ['payment_number' => $this->numbers->next('SUPPLIER_PAYMENT'), 'status' => Status::PENDING, 'recorded_by' => $actor->id, 'currency' => 'NGN']);
            $this->audit->record('supplier_payment.created', $payment, $actor, 'Pending supplier payment recorded', [], $payment->only(['supplier_id', 'amount', 'currency', 'payment_method', 'status']));

            return $payment;
        }, 5);
    }

    public function complete(SupplierPayment $payment, Staff $actor): SupplierPayment
    {
        Gate::forUser($actor)->authorize('approve supplier payments');

        return DB::transaction(function () use ($payment, $actor) {
            $payment = $this->locked($payment);
            $this->pending($payment);
            if ($payment->recorded_by === $actor->id) {
                throw ValidationException::withMessages(['approval' => 'Another staff member must approve this payment.']);
            }
            $payment->update(['status' => Status::COMPLETED, 'approved_by' => $actor->id, 'paid_at' => $payment->paid_at ?? now()]);
            $this->audit->record('supplier_payment.completed', $payment, $actor, 'Supplier payment approved and completed', [], ['amount' => $payment->amount, 'status' => $payment->status->value]);

            return $payment;
        }, 5);
    }

    public function cancel(SupplierPayment $payment, Staff $actor): SupplierPayment
    {
        return $this->finishPending($payment, $actor, Status::CANCELLED);
    }

    public function fail(SupplierPayment $payment, Staff $actor): SupplierPayment
    {
        return $this->finishPending($payment, $actor, Status::FAILED);
    }

    private function finishPending(SupplierPayment $payment, Staff $actor, Status $status): SupplierPayment
    {
        Gate::forUser($actor)->authorize('approve supplier payments');

        return DB::transaction(function () use ($payment, $actor, $status) {
            $payment = $this->locked($payment);
            $this->pending($payment);
            $payment->update(['status' => $status]);
            $this->audit->record('supplier_payment.'.strtolower($status->value), $payment, $actor, 'Supplier payment '.strtolower($status->value));

            return $payment;
        });
    }

    public function allocate(SupplierPayment $payment, array $allocations, Staff $actor): SupplierPayment
    {
        Gate::forUser($actor)->authorize('allocate supplier payments');
        Validator::make(['allocations' => $allocations], [
            'allocations' => ['required', 'array', 'min:1', 'max:200'],
            'allocations.*.supplier_invoice_id' => ['required', 'integer', 'distinct', 'exists:supplier_invoices,id'],
            'allocations.*.amount' => ['required'],
        ])->validate();

        return DB::transaction(function () use ($payment, $allocations, $actor) {
            $payment = $this->locked($payment);
            if ($payment->status !== Status::COMPLETED) {
                throw ValidationException::withMessages(['status' => 'Only completed payments can be allocated.']);
            }
            usort($allocations, fn ($a, $b) => $a['supplier_invoice_id'] <=> $b['supplier_invoice_id']);
            $invoices = SupplierInvoice::whereIn('id', array_column($allocations, 'supplier_invoice_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $used = BigDecimal::of($payment->allocated_amount);
            foreach ($allocations as $line) {
                $invoice = $invoices->get($line['supplier_invoice_id']);
                if (! $invoice || $invoice->supplier_id !== $payment->supplier_id) {
                    throw ValidationException::withMessages(['allocations' => 'All invoices must belong to the payment supplier.']);
                }
                if ($payment->allocations()->where('supplier_invoice_id', $invoice->id)->exists()) {
                    throw ValidationException::withMessages(['allocations' => 'This payment already has an allocation to that invoice.']);
                }
                $amount = Decimal::value($line['amount'], 2, true, 'amount');
                $used = $used->plus($amount);
                if ($used->isGreaterThan($payment->amount)) {
                    throw ValidationException::withMessages(['allocations' => 'Allocations exceed the payment amount.']);
                }
                $invoice = $this->invoices->refreshPaymentBalance($invoice);
                if (BigDecimal::of($amount)->isGreaterThan($invoice->amount_due)) {
                    throw ValidationException::withMessages(['allocations' => 'Allocation exceeds invoice outstanding balance.']);
                }
                $allocation = $payment->allocations()->create(['supplier_invoice_id' => $invoice->id, 'amount' => $amount]);
                $this->invoices->refreshPaymentBalance($invoice);
                $this->audit->record('supplier_payment.allocated', $allocation, $actor, 'Supplier payment allocated', [], ['supplier_payment_id' => $payment->id, 'supplier_invoice_id' => $invoice->id, 'amount' => $amount]);
            }

            return $payment->refresh()->load('allocations.supplierInvoice');
        }, 5);
    }

    private function locked(SupplierPayment $payment): SupplierPayment
    {
        Supplier::withTrashed()->whereKey($payment->supplier_id)->lockForUpdate()->firstOrFail();

        return SupplierPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
    }

    private function pending(SupplierPayment $payment): void
    {
        if ($payment->status !== Status::PENDING) {
            throw ValidationException::withMessages(['status' => 'Only pending payments can change state.']);
        }
    }
}
