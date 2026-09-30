<?php

namespace App\Services;

use App\Enums\GoodsReceiptStatus as Status;
use App\Enums\PurchaseOrderStatus;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\PurchaseOrder;
use App\Models\Staff;
use App\Models\Supplier;
use App\Support\InventoryDecimal as Decimal;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class GoodsReceiptService
{
    public function __construct(private SupplierService $suppliers, private InventoryService $inventory, private NumberSequenceService $numbers, private ActivityLogService $audit) {}

    public function create(array $data, Staff $actor): GoodsReceipt
    {
        Gate::forUser($actor)->authorize('create goods receipts');

        return $this->save(new GoodsReceipt, $data, $actor);
    }

    public function update(GoodsReceipt $receipt, array $data, Staff $actor): GoodsReceipt
    {
        Gate::forUser($actor)->authorize('update goods receipts');

        return $this->save($receipt, $data, $actor);
    }

    private function save(GoodsReceipt $receipt, array $data, Staff $actor): GoodsReceipt
    {
        return DB::transaction(function () use ($receipt, $data, $actor) {
            $data = Validator::make($data, [
                'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
                'purchase_order_id' => ['required', 'integer', 'exists:purchase_orders,id'],
                'inventory_location_id' => ['required', 'integer', 'exists:inventory_locations,id'],
                'received_at' => ['required', 'date'], 'supplier_delivery_note' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string', 'max:5000'],
                'items' => ['required', 'array', 'min:1', 'max:200'], 'items.*.purchase_order_item_id' => ['required', 'integer', 'distinct'],
                'items.*.inventory_item_id' => ['sometimes', 'integer'], 'items.*.quantity_received' => ['required'],
                'items.*.quantity_accepted' => ['required'], 'items.*.quantity_rejected' => ['required'],
                'items.*.unit_cost' => ['required'], 'items.*.rejection_reason' => ['nullable', 'string', 'max:5000'],
            ])->validate();
            if ($receipt->exists && ((int) $data['supplier_id'] !== $receipt->supplier_id || (int) $data['purchase_order_id'] !== $receipt->purchase_order_id)) {
                throw ValidationException::withMessages(['purchase_order_id' => 'The supplier and purchase order cannot be replaced.']);
            }
            $this->suppliers->lockActive((int) $data['supplier_id']);
            $order = $this->lockOrder((int) $data['purchase_order_id'], (int) $data['supplier_id']);
            if ($receipt->exists) {
                $receipt = GoodsReceipt::whereKey($receipt->id)->lockForUpdate()->firstOrFail();
                $this->requireStatus($receipt, [Status::DRAFT]);
            }
            $lines = $this->validatedLines($order, $data['items']);
            $items = InventoryItem::whereIn('id', array_column($lines, 'inventory_item_id'))->orderBy('id')->lockForUpdate()->get();
            if ($items->count() !== count($lines) || $items->contains(fn ($item) => ! $item->is_active || ! $item->is_stock_tracked)) {
                throw ValidationException::withMessages(['items' => 'Receipt items must be active and stock tracked.']);
            }
            $location = InventoryLocation::whereKey($data['inventory_location_id'])->lockForUpdate()->firstOrFail();
            if (! $location->is_active) {
                throw ValidationException::withMessages(['inventory_location_id' => 'Location must be active.']);
            }
            unset($data['items']);
            $event = $receipt->exists ? 'updated' : 'created';
            if (! $receipt->exists) {
                $receipt->fill(['goods_receipt_number' => $this->numbers->next('GOODS_RECEIPT'), 'received_by' => $actor->id, 'status' => Status::DRAFT]);
            }
            $receipt->fill($data)->save();
            $receipt->items()->delete();
            $receipt->items()->createMany($lines);
            $this->audit->record('goods_receipt.'.$event, $receipt, $actor, 'Goods receipt '.$event, [], $receipt->only(['purchase_order_id', 'supplier_id', 'inventory_location_id', 'status']));

            return $receipt->load('items');
        }, 5);
    }

    public function inspect(GoodsReceipt $receipt, Staff $actor): GoodsReceipt
    {
        Gate::forUser($actor)->authorize('inspect goods receipts');

        return DB::transaction(function () use ($receipt, $actor) {
            $this->suppliers->lockActive($receipt->supplier_id);
            $order = $this->lockOrder($receipt->purchase_order_id, $receipt->supplier_id);
            $receipt = GoodsReceipt::whereKey($receipt->id)->lockForUpdate()->firstOrFail();
            $this->requireStatus($receipt, [Status::DRAFT]);
            $this->validatedLines($order, $receipt->items()->get()->toArray());
            $receipt->update(['status' => Status::INSPECTED, 'inspected_by' => $actor->id]);
            $this->audit->record('goods_receipt.inspected', $receipt, $actor, 'Receipt inspection confirmed');

            return $receipt;
        }, 5);
    }

    public function reject(GoodsReceipt $receipt, Staff $actor, string $reason): GoodsReceipt
    {
        Gate::forUser($actor)->authorize('inspect goods receipts');
        Validator::make(['reason' => $reason], ['reason' => ['required', 'string', 'max:5000']])->validate();

        return $this->finishWithoutStock($receipt, $actor, Status::REJECTED, $reason);
    }

    public function cancel(GoodsReceipt $receipt, Staff $actor): GoodsReceipt
    {
        Gate::forUser($actor)->authorize('cancel goods receipts');

        return $this->finishWithoutStock($receipt, $actor, Status::CANCELLED);
    }

    private function finishWithoutStock(GoodsReceipt $receipt, Staff $actor, Status $status, ?string $reason = null): GoodsReceipt
    {
        return DB::transaction(function () use ($receipt, $actor, $status, $reason) {
            Supplier::withTrashed()->whereKey($receipt->supplier_id)->lockForUpdate()->firstOrFail();
            $receipt = GoodsReceipt::whereKey($receipt->id)->lockForUpdate()->firstOrFail();
            $this->requireStatus($receipt, [Status::DRAFT, Status::INSPECTED]);
            $receipt->status = $status;
            $receipt->save();
            $this->audit->record('goods_receipt.'.strtolower($status->value), $receipt, $actor, 'Receipt '.strtolower($status->value), [], [], ['reason' => $reason]);

            return $receipt;
        });
    }

    public function post(GoodsReceipt $receipt, Staff $actor): GoodsReceipt
    {
        Gate::forUser($actor)->authorize('post goods receipts');

        return DB::transaction(function () use ($receipt, $actor) {
            $this->suppliers->lockActive($receipt->supplier_id);
            $order = $this->lockOrder($receipt->purchase_order_id, $receipt->supplier_id);
            $receipt = GoodsReceipt::whereKey($receipt->id)->lockForUpdate()->firstOrFail();
            $this->requireStatus($receipt, [Status::INSPECTED]);
            $this->validatedLines($order, $receipt->items()->get()->toArray());
            $this->inventory->postGoodsReceipt($receipt, $actor);
            // Posted accepted receipt lines, not drafts or rejected quantities, are authoritative.
            $complete = true;
            $any = false;
            foreach ($order->items()->orderBy('id')->lockForUpdate()->get() as $line) {
                $accepted = $this->postedAccepted($line->id);
                $line->quantity_received = $accepted;
                $line->save();
                if (BigDecimal::of($accepted)->isLessThan($line->quantity_ordered)) {
                    $complete = false;
                }
                if (BigDecimal::of($accepted)->isPositive()) {
                    $any = true;
                }
            }
            $order->status = $complete ? PurchaseOrderStatus::RECEIVED : ($any ? PurchaseOrderStatus::PARTIALLY_RECEIVED : PurchaseOrderStatus::APPROVED);
            $order->save();
            $receipt->refresh();
            $this->audit->record('goods_receipt.posted', $receipt, $actor, 'Accepted goods posted to inventory', [], ['purchase_order_id' => $order->id, 'purchase_order_status' => $order->status->value]);

            return $receipt;
        }, 5);
    }

    private function lockOrder(int $id, int $supplierId): PurchaseOrder
    {
        $order = PurchaseOrder::whereKey($id)->lockForUpdate()->firstOrFail();
        if ($order->supplier_id !== $supplierId || ! in_array($order->status, [PurchaseOrderStatus::APPROVED, PurchaseOrderStatus::PARTIALLY_RECEIVED], true)) {
            throw ValidationException::withMessages(['purchase_order_id' => 'Choose an approved, open PO for this supplier.']);
        }

        return $order;
    }

    private function validatedLines(PurchaseOrder $order, array $inputs): array
    {
        if (! $inputs) {
            throw ValidationException::withMessages(['items' => 'At least one receipt item is required.']);
        }
        $poItems = $order->items()->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $lines = [];
        $seen = [];
        foreach ($inputs as $input) {
            $po = $poItems->get($input['purchase_order_item_id'] ?? null);
            if (! $po || isset($seen[$po->id]) || (isset($input['inventory_item_id']) && (int) $input['inventory_item_id'] !== $po->inventory_item_id)) {
                throw ValidationException::withMessages(['items' => 'Receipt items must uniquely match this purchase order.']);
            }
            $seen[$po->id] = true;
            $received = Decimal::value($input['quantity_received'], 3, true, 'quantity_received');
            $accepted = Decimal::value($input['quantity_accepted'], 3, false, 'quantity_accepted');
            $rejected = Decimal::value($input['quantity_rejected'], 3, false, 'quantity_rejected');
            if (! BigDecimal::of($accepted)->plus($rejected)->isEqualTo($received)) {
                throw ValidationException::withMessages(['items' => 'Accepted plus rejected must equal received.']);
            }
            if (BigDecimal::of($rejected)->isPositive() && blank($input['rejection_reason'] ?? null)) {
                throw ValidationException::withMessages(['rejection_reason' => 'Explain rejected quantities.']);
            }
            $remaining = BigDecimal::of($po->quantity_ordered)->minus($this->postedAccepted($po->id));
            if (BigDecimal::of($received)->isGreaterThan($remaining)) {
                throw ValidationException::withMessages(['items' => 'Receipt exceeds the remaining PO quantity.']);
            }
            $lines[] = ['purchase_order_item_id' => $po->id, 'inventory_item_id' => $po->inventory_item_id,
                'quantity_received' => $received, 'quantity_accepted' => $accepted, 'quantity_rejected' => $rejected,
                'unit_cost' => Decimal::value($input['unit_cost'], 4, false, 'unit_cost'), 'rejection_reason' => $input['rejection_reason'] ?? null];
        }

        return $lines;
    }

    private function postedAccepted(int $poItemId): string
    {
        $sum = BigDecimal::zero();
        foreach (GoodsReceiptItem::where('purchase_order_item_id', $poItemId)->whereHas('goodsReceipt', fn ($q) => $q->where('status', Status::POSTED))->get(['quantity_accepted']) as $item) {
            $sum = $sum->plus($item->quantity_accepted);
        }

        return (string) $sum->toScale(3);
    }

    private function requireStatus(GoodsReceipt $receipt,array $allowed): void
    {
        if (! in_array($receipt->status,$allowed,true)) {
            throw ValidationException::withMessages(['status' => 'Receipt is not in an editable/postable state.']);
        }
    }
}
