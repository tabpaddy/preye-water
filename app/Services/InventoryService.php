<?php

namespace App\Services;

use App\Enums\GoodsReceiptStatus;
use App\Enums\StockAdjustmentStatus;
use App\Enums\StockMovementType;
use App\Models\GoodsReceipt;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryStock;
use App\Models\Staff;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Support\InventoryDecimal as Decimal;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /**
     * Restricted integration primitive, not a receiving UI. Future procurement must
     * authorize its own document workflow before calling inventory.
     */
    public function receive(InventoryItem $item, InventoryLocation $location, mixed $quantity, mixed $unitCost, Staff $actor): StockMovement
    {
        Gate::forUser($actor)->authorize('operate inventory');

        return DB::transaction(function () use ($item, $location, $quantity, $unitCost, $actor) {
            $stock = $this->lockStocks([$item->id], [$location->id])[$item->id.':'.$location->id];

            return $this->move($stock, null, Decimal::value($quantity, 3, true), $unitCost === null ? null : Decimal::value($unitCost, 4, false, 'unit_cost'), StockMovementType::ADJUSTMENT_IN, $actor, ['notes' => 'Authorized inventory integration receipt']);
        }, 5);
    }

    public function outbound(InventoryItem $item, InventoryLocation $location, mixed $quantity, Staff $actor, mixed $releaseReserved = '0'): StockMovement
    {
        Gate::forUser($actor)->authorize('operate inventory');

        return DB::transaction(function () use ($item, $location, $quantity, $actor, $releaseReserved) {
            $stock = $this->lockStocks([$item->id], [$location->id])[$item->id.':'.$location->id];
            $quantity = Decimal::value($quantity, 3, true);
            $releaseReserved = Decimal::value($releaseReserved);
            if (BigDecimal::of($releaseReserved)->isGreaterThan($quantity) || BigDecimal::of($releaseReserved)->isGreaterThan($stock->quantity_reserved)) {
                $this->fail('Invalid reserved quantity release.');
            }
            $stock->quantity_reserved = (string) BigDecimal::of($stock->quantity_reserved)->minus($releaseReserved);

            return $this->move(null, $stock, $quantity, null, StockMovementType::ADJUSTMENT_OUT, $actor, ['notes' => 'Authorized inventory integration outflow']);
        }, 5);
    }

    public function transfer(InventoryItem $item, InventoryLocation $from, InventoryLocation $to, mixed $quantity, Staff $actor): StockMovement
    {
        Gate::forUser($actor)->authorize('transfer inventory');
        if ($from->id === $to->id) {
            $this->fail('Source and destination must differ.');
        }

        return DB::transaction(function () use ($item, $from, $to, $quantity, $actor) {
            $stocks = $this->lockStocks([$item->id], [$from->id, $to->id]);

            return $this->move($stocks[$item->id.':'.$to->id], $stocks[$item->id.':'.$from->id], Decimal::value($quantity, 3, true), null, StockMovementType::TRANSFER, $actor);
        }, 5);
    }

    public function reserve(InventoryItem $item, InventoryLocation $location, mixed $quantity, Staff $actor): InventoryStock
    {
        return $this->reservation($item, $location, $quantity, $actor, false);
    }

    public function releaseReservation(InventoryItem $item, InventoryLocation $location, mixed $quantity, Staff $actor): InventoryStock
    {
        return $this->reservation($item, $location, $quantity, $actor, true);
    }

    private function reservation(InventoryItem $item, InventoryLocation $location, mixed $quantity, Staff $actor, bool $release): InventoryStock
    {
        Gate::forUser($actor)->authorize('reserve inventory');

        return DB::transaction(function () use ($item, $location, $quantity, $release) {
            // Release remains possible after deactivation so existing reservations can be unwound.
            $stock = $this->lockStocks([$item->id], [$location->id], ! $release)[$item->id.':'.$location->id];
            $quantity = BigDecimal::of(Decimal::value($quantity, 3, true));
            if ($quantity->isGreaterThan($release ? $stock->quantity_reserved : $stock->available_quantity)) {
                $this->fail($release ? 'Cannot release more than reserved.' : 'Insufficient available stock.');
            }
            $stock->quantity_reserved = (string) ($release ? BigDecimal::of($stock->quantity_reserved)->minus($quantity) : BigDecimal::of($stock->quantity_reserved)->plus($quantity));
            $stock->save();

            return $stock;
        }, 5);
    }

    public function availableQuantity(InventoryItem $item, InventoryLocation $location): string
    {
        return InventoryStock::where('inventory_item_id', $item->id)->where('inventory_location_id', $location->id)->first()?->available_quantity ?? '0.000';
    }

    /**
     * Trusted document integration. GoodsReceiptService owns PO validation and
     * the outer transaction; this method owns physical balances and ledger writes.
     */
    public function postGoodsReceipt(GoodsReceipt $receipt, Staff $actor): void
    {
        Gate::forUser($actor)->authorize('post goods receipts');
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Goods receipt posting requires a transaction.');
        }
        $receipt = GoodsReceipt::whereKey($receipt->id)->lockForUpdate()->firstOrFail();
        if ($receipt->status !== GoodsReceiptStatus::INSPECTED) {
            $this->fail('Only inspected goods receipts can be posted.');
        }
        $items = $receipt->items()->orderBy('inventory_item_id')->get();
        if ($items->isEmpty()) {
            $this->fail('The receipt has no items.');
        }
        $stocks = $this->lockStocks($items->pluck('inventory_item_id')->all(), [$receipt->inventory_location_id]);
        foreach ($items as $line) {
            if (BigDecimal::of($line->quantity_accepted)->isZero()) {
                continue;
            }
            $this->move($stocks[$line->inventory_item_id.':'.$receipt->inventory_location_id], null,
                $line->quantity_accepted, $line->unit_cost, StockMovementType::SUPPLIER_RECEIPT_IN, $actor, [
                    'reference_type' => GoodsReceipt::class, 'reference_id' => $receipt->id,
                    'reference_number' => $receipt->goods_receipt_number, 'notes' => $receipt->supplier_delivery_note,
                ], $receipt->received_at);
        }
        $receipt->update(['status' => GoodsReceiptStatus::POSTED, 'posted_by' => $actor->id, 'posted_at' => now()]);
    }

    /** Internal read under the caller's transaction; creates only a zero balance. */
    public function snapshot(int $itemId, int $locationId): InventoryStock
    {
        return $this->lockStocks([$itemId], [$locationId])[$itemId.':'.$locationId];
    }

    /** Called only by the adjustment workflow; still validates authorization and state. */
    public function postAdjustment(StockAdjustment $adjustment, Staff $actor): void
    {
        Gate::forUser($actor)->authorize('post stock adjustments');
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Adjustment posting requires a transaction.');
        }
        $adjustment = StockAdjustment::whereKey($adjustment->id)->lockForUpdate()->firstOrFail();
        if ($adjustment->status !== StockAdjustmentStatus::APPROVED) {
            $this->fail('Only approved adjustments can be posted.');
        }
        $items = $adjustment->items()->orderBy('inventory_item_id')->get();
        if ($items->isEmpty()) {
            $this->fail('An adjustment requires items.');
        }
        $stocks = $this->lockStocks($items->pluck('inventory_item_id')->all(), [$adjustment->inventory_location_id]);
        foreach ($items as $line) {
            $stock = $stocks[$line->inventory_item_id.':'.$adjustment->inventory_location_id];
            if (! BigDecimal::of($stock->quantity_on_hand)->isEqualTo($line->system_quantity)) {
                $this->fail('This count is stale. Cancel it and create a new count.');
            }
            $difference = BigDecimal::of($line->difference_quantity);
            if ($difference->isZero()) {
                continue;
            }
            $in = $difference->isPositive();
            $this->move($in ? $stock : null, $in ? null : $stock, (string) $difference->abs(), null, $in ? StockMovementType::ADJUSTMENT_IN : StockMovementType::ADJUSTMENT_OUT, $actor, [
                'reference_type' => StockAdjustment::class, 'reference_id' => $adjustment->id,
                'reference_number' => $adjustment->adjustment_number, 'notes' => $line->notes,
            ]);
        }
        $adjustment->update(['status' => StockAdjustmentStatus::POSTED, 'posted_at' => now()]);
    }

    /**
     * Item locks serialize first-row creation as well as all writes for an item.
     * Acquire every item, then every location, then balances in ascending key order.
     * The unique item/location constraint is the final defense against duplicate rows.
     */
    private function lockStocks(array $itemIds, array $locationIds, bool $requireActive = true): array
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Stock locks require a transaction.');
        }
        $itemIds = array_values(array_unique($itemIds));
        sort($itemIds);
        $locationIds = array_values(array_unique($locationIds));
        sort($locationIds);
        foreach ($itemIds as $id) {
            $item = InventoryItem::withTrashed()->whereKey($id)->lockForUpdate()->firstOrFail();
            if ($requireActive && (! $item->is_active || $item->trashed() || ! $item->is_stock_tracked)) {
                $this->fail('Item must be active and stock tracked.');
            }
        }
        foreach ($locationIds as $id) {
            $location = InventoryLocation::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($requireActive && ! $location->is_active) {
                $this->fail('Location must be active.');
            }
        }
        $stocks = [];
        foreach ($itemIds as $itemId) {
            foreach ($locationIds as $locationId) {
                // Parent locks prevent concurrent service callers from both seeing absence.
                $stock = InventoryStock::where('inventory_item_id', $itemId)->where('inventory_location_id', $locationId)->lockForUpdate()->first();
                $stocks[$itemId.':'.$locationId] = $stock ?? InventoryStock::create(['inventory_item_id' => $itemId, 'inventory_location_id' => $locationId, 'quantity_on_hand' => '0.000', 'quantity_reserved' => '0.000']);
            }
        }

        return $stocks;
    }

    private function move(?InventoryStock $to, ?InventoryStock $from, string $quantity, ?string $cost, StockMovementType $type, Staff $actor, array $reference = [], ?\DateTimeInterface $occurredAt = null): StockMovement
    {
        $quantity = Decimal::value($quantity, 3, true);
        if ($from) {
            if (BigDecimal::of($quantity)->isGreaterThan($from->available_quantity)) {
                $this->fail('Insufficient available stock; reserved stock cannot be issued.');
            }
            $cost = $from->average_unit_cost;
            $from->quantity_on_hand = (string) BigDecimal::of($from->quantity_on_hand)->minus($quantity);
            $from->save();
        }
        if ($cost !== null) {
            $cost = Decimal::value($cost, 4, false, 'unit_cost');
        }
        if ($to) {
            // Uncosted count gains retain the known average; unknown stock remains unknown.
            if (! $from && $cost === null) {
                $cost = $to->average_unit_cost;
            }
            $oldQuantity = BigDecimal::of($to->quantity_on_hand);
            $newQuantity = $oldQuantity->plus($quantity);
            if ($cost !== null && ($oldQuantity->isZero() || $to->average_unit_cost !== null)) {
                $value = $oldQuantity->multipliedBy($to->average_unit_cost ?? '0')->plus(BigDecimal::of($quantity)->multipliedBy($cost));
                $to->average_unit_cost = Decimal::value((string) $value->dividedBy($newQuantity, 4, RoundingMode::HALF_UP), 4, false, 'average_unit_cost');
            } else {
                $to->average_unit_cost = null;
            }
            $to->quantity_on_hand = Decimal::value((string) $newQuantity);
            $to->save();
        }

        return StockMovement::create(array_merge($reference, [
            'inventory_item_id' => ($from ?? $to)->inventory_item_id,
            'from_location_id' => $from?->inventory_location_id, 'to_location_id' => $to?->inventory_location_id,
            'movement_type' => $type, 'quantity' => $quantity, 'unit_cost' => $cost, 'total_cost' => Decimal::cost($quantity, $cost),
            'performed_by' => $actor->id, 'occurred_at' => $occurredAt ?? now(),
        ]));
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['inventory' => $message]);
    }
}
