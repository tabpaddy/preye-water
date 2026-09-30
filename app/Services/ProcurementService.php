<?php

namespace App\Services;

use App\Enums\PurchaseOrderStatus as Status;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\Staff;
use App\Models\Supplier;
use App\Support\ProcurementTotals;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProcurementService
{
    public function __construct(private SupplierService $suppliers, private NumberSequenceService $numbers, private ActivityLogService $audit) {}

    public function create(array $data, Staff $actor): PurchaseOrder
    {
        Gate::forUser($actor)->authorize('create purchase orders');

        return $this->save(new PurchaseOrder, $data, $actor);
    }

    public function update(PurchaseOrder $order, array $data, Staff $actor): PurchaseOrder
    {
        Gate::forUser($actor)->authorize('update purchase orders');

        return $this->save($order, $data, $actor);
    }

    private function save(PurchaseOrder $order, array $data, Staff $actor): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $data, $actor) {
            $data = Validator::make($data, [
                'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
                'order_date' => ['required', 'date_format:Y-m-d'],
                'expected_delivery_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:order_date'],
                'supplier_reference' => ['nullable', 'string', 'max:255'],
                'notes' => ['nullable', 'string', 'max:5000'],
                'discount_amount' => ['sometimes'],
                'tax_amount' => ['sometimes'],
                'other_cost' => ['sometimes'],
                'items' => ['required', 'array', 'min:1', 'max:200'],
                'items.*.inventory_item_id' => ['required', 'integer', 'distinct', 'exists:inventory_items,id'],
                'items.*.quantity_ordered' => ['required'],
                'items.*.unit_cost' => ['required'],
                'items.*.discount_amount' => ['sometimes'],
                'items.*.tax_amount' => ['sometimes'],
            ])->validate();
            if ($order->exists && (int) $data['supplier_id'] !== $order->supplier_id) {
                throw ValidationException::withMessages(['supplier_id' => 'Create a new order to change supplier.']);
            }
            $this->suppliers->lockActive((int) $data['supplier_id']);
            if ($order->exists) {
                $order = PurchaseOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
                $this->requireStatus($order, [Status::DRAFT]);
            }
            $catalog = InventoryItem::whereIn('id', array_column($data['items'], 'inventory_item_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $lines = [];
            foreach ($data['items'] as $input) {
                $item = $catalog->get($input['inventory_item_id']);
                if (! $item || ! $item->is_active) {
                    throw ValidationException::withMessages(['items' => 'Choose active inventory items.']);
                }
                $lines[] = ProcurementTotals::line($input, 'quantity_ordered') + ['inventory_item_id' => $item->id, 'item_name' => $item->name, 'sku' => $item->sku, 'quantity_received' => '0.000'];
            }
            $totals = ProcurementTotals::document($lines, $data, 'other_cost');
            unset($data['items']);
            $data = array_merge($data, $totals);
            $event = $order->exists ? 'updated' : 'created';
            $old = $order->only(['status', 'total_amount']);
            if (! $order->exists) {
                $order->fill(['purchase_order_number' => $this->numbers->next('PURCHASE_ORDER'), 'created_by' => $actor->id, 'status' => Status::DRAFT]);
            }
            $order->fill($data)->save();
            $order->items()->delete();
            $order->items()->createMany($lines);
            $this->audit->record('purchase_order.' . $event, $order, $actor, 'Purchase order ' . $event, $old, $order->only(['supplier_id', 'total_amount', 'status']));

            return $order->load('items');
        }, 5);
    }

    public function submit(PurchaseOrder $order, Staff $actor): PurchaseOrder
    {
        return $this->transition($order, $actor, 'submit', [Status::DRAFT], Status::PENDING_APPROVAL);
    }

    public function approve(PurchaseOrder $order, Staff $actor): PurchaseOrder
    {
        return $this->transition($order, $actor, 'approve', [Status::PENDING_APPROVAL], Status::APPROVED);
    }

    public function cancel(PurchaseOrder $order, Staff $actor): PurchaseOrder
    {
        return $this->transition($order, $actor, 'cancel', [Status::DRAFT, Status::PENDING_APPROVAL, Status::APPROVED], Status::CANCELLED);
    }

    public function close(PurchaseOrder $order, Staff $actor): PurchaseOrder
    {
        return $this->transition($order, $actor, 'close', [Status::RECEIVED], Status::CLOSED);
    }

    private function transition(PurchaseOrder $order, Staff $actor, string $operation, array $allowed, Status $to): PurchaseOrder
    {
        Gate::forUser($actor)->authorize($operation . ' purchase orders');

        return DB::transaction(function () use ($order, $actor, $operation, $allowed, $to) {
            if (in_array($operation, ['submit', 'approve'])) {
                $this->suppliers->lockActive($order->supplier_id);
            } else {
                Supplier::withTrashed()->whereKey($order->supplier_id)->lockForUpdate()->firstOrFail();
            }
            $order = PurchaseOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $this->requireStatus($order, $allowed);
            if ($operation === 'approve') {
                if ($order->created_by === $actor->id) {
                    throw ValidationException::withMessages(['approval' => 'Another staff member must approve the order.']);
                }
                $items = $order->items()->with('inventoryItem')->get();
                if ($items->isEmpty() || $items->contains(fn($line) => ! $line->inventoryItem || ! $line->inventoryItem->is_active || $line->inventoryItem->trashed())) {
                    throw ValidationException::withMessages(['items' => 'Order contains unavailable items.']);
                }
                $lines = $items->map(fn($line) => ProcurementTotals::line($line->toArray(), 'quantity_ordered'))->all();
                $totals = ProcurementTotals::document($lines, $order->toArray(), 'other_cost');
                if ($totals['total_amount'] !== $order->total_amount) {
                    throw ValidationException::withMessages(['total_amount' => 'Order totals are inconsistent.']);
                }
                $order->approved_by = $actor->id;
                $order->approved_at = now();
            }
            if ($operation === 'cancel' && $order->goodsReceipts()->where('status', 'POSTED')->exists()) {
                throw ValidationException::withMessages(['status' => 'A received order cannot be cancelled.']);
            }
            $old = $order->status->value;
            $order->status = $to;
            $order->save();
            $this->audit->record('purchase_order.' . strtolower($to->value), $order, $actor, 'Purchase order ' . $operation, ['status' => $old], ['status' => $to->value]);

            return $order;
        }, 5);
    }

    private function requireStatus(PurchaseOrder $order, array $allowed): void
    {
        if (! in_array($order->status, $allowed, true)) {
            throw ValidationException::withMessages(['status' => 'Operation is not allowed in the current PO state.']);
        }
    }
}
