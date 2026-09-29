<?php

namespace App\Services;

use App\Enums\StockAdjustmentReason;
use App\Enums\StockAdjustmentStatus as Status;
use App\Models\InventoryItem;
use App\Models\Staff;
use App\Models\StockAdjustment;
use App\Support\InventoryDecimal;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StockAdjustmentService
{
    public function __construct(private InventoryService $inventory, private NumberSequenceService $numbers, private ActivityLogService $audit) {}

    public function create(array $data, Staff $actor): StockAdjustment
    {
        Gate::forUser($actor)->authorize('create stock adjustments');

        return DB::transaction(function () use ($data, $actor) {
            $data = $this->validate($data);
            $items = $data['items'];
            unset($data['items']);
            $record = StockAdjustment::create($data + ['created_by' => $actor->id, 'status' => Status::DRAFT, 'adjustment_number' => $this->numbers->next('STOCK_ADJUSTMENT')]);
            $this->writeItems($record, $items);
            $this->audit->record('stock_adjustment.created', $record, $actor, 'Stock adjustment created', [], $record->load('items')->toArray());

            return $record;
        }, 5);
    }

    public function update(StockAdjustment $record, array $data, Staff $actor): StockAdjustment
    {
        Gate::forUser($actor)->authorize('update stock adjustments');

        return DB::transaction(function () use ($record, $data, $actor) {
            $record = $this->locked($record);
            $this->requireStatus($record, [Status::DRAFT]);
            $data = $this->validate($data);
            $old = $record->load('items')->toArray();
            $items = $data['items'];
            unset($data['items']);
            $record->update($data);
            $this->writeItems($record, $items);
            $this->audit->record('stock_adjustment.updated', $record, $actor, 'Draft count refreshed', $old, $record->refresh()->load('items')->toArray());

            return $record;
        }, 5);
    }

    public function submit(StockAdjustment $record, Staff $actor): StockAdjustment
    {
        return $this->transition($record, $actor, 'submit', [Status::DRAFT], Status::PENDING_APPROVAL);
    }

    public function approve(StockAdjustment $record, Staff $actor): StockAdjustment
    {
        return $this->transition($record, $actor, 'approve', [Status::PENDING_APPROVAL], Status::APPROVED);
    }

    public function reject(StockAdjustment $record, Staff $actor, string $reason): StockAdjustment
    {
        Validator::make(['reason' => $reason], ['reason' => ['required', 'string', 'max:5000']])->validate();

        return $this->transition($record, $actor, 'approve', [Status::PENDING_APPROVAL], Status::REJECTED, $reason);
    }

    public function cancel(StockAdjustment $record, Staff $actor): StockAdjustment
    {
        return $this->transition($record, $actor, 'cancel', [Status::DRAFT, Status::PENDING_APPROVAL, Status::APPROVED], Status::CANCELLED);
    }

    public function post(StockAdjustment $record, Staff $actor): StockAdjustment
    {
        Gate::forUser($actor)->authorize('post stock adjustments');

        return DB::transaction(function () use ($record, $actor) {
            $record = $this->locked($record);
            $this->requireStatus($record, [Status::APPROVED]);
            $this->inventory->postAdjustment($record, $actor);
            $record->refresh();
            $this->audit->record('stock_adjustment.posted', $record, $actor, 'Stock adjustment posted', [], ['status' => $record->status->value]);

            return $record;
        }, 5);
    }

    private function transition(StockAdjustment $record, Staff $actor, string $permission, array $from, Status $to, ?string $reason = null): StockAdjustment
    {
        Gate::forUser($actor)->authorize($permission.' stock adjustments');

        return DB::transaction(function () use ($record, $actor, $from, $to, $reason) {
            $record = $this->locked($record);
            $this->requireStatus($record, $from);
            if (in_array($to, [Status::APPROVED, Status::REJECTED], true) && $record->created_by === $actor->id) {
                throw ValidationException::withMessages(['approval' => 'A different staff member must review this adjustment.']);
            }
            if (! $record->items()->exists()) {
                throw ValidationException::withMessages(['items' => 'Add at least one count item.']);
            }
            $old = $record->status->value;
            $record->status = $to;
            if ($to === Status::APPROVED) {
                $record->approved_by = $actor->id;
                $record->approved_at = now();
            }
            $record->save();
            $this->audit->record('stock_adjustment.'.strtolower($to->value), $record, $actor, 'Stock adjustment '.strtolower($to->value), ['status' => $old], ['status' => $to->value], ['reason' => $reason]);

            return $record;
        });
    }

    private function validate(array $data): array
    {
        return Validator::make($data, [
            'inventory_location_id' => ['required', 'integer', Rule::exists('inventory_locations', 'id')->where('is_active', true)],
            'reason' => ['required', Rule::enum(StockAdjustmentReason::class)], 'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.inventory_item_id' => ['required', 'integer', 'distinct', Rule::exists('inventory_items', 'id')->whereNull('deleted_at')->where('is_active', true)->where('is_stock_tracked', true)],
            'items.*.counted_quantity' => ['required'], 'items.*.notes' => ['nullable', 'string', 'max:5000'],
        ])->validate();
    }

    private function writeItems(StockAdjustment $record, array $items): void
    {
        // Re-saving a draft is an explicit recount; submitted snapshots are frozen.
        usort($items, fn ($a, $b) => $a['inventory_item_id'] <=> $b['inventory_item_id']);
        // Lock all item parents first, matching InventoryService's multi-item ordering.
        InventoryItem::whereIn('id', array_column($items, 'inventory_item_id'))->orderBy('id')->lockForUpdate()->get();
        $record->items()->delete();
        foreach ($items as $line) {
            $stock = $this->inventory->snapshot((int) $line['inventory_item_id'], $record->inventory_location_id);
            $count = InventoryDecimal::value($line['counted_quantity'], 3, false, 'items');
            $record->items()->create([
                'inventory_item_id' => $line['inventory_item_id'], 'system_quantity' => $stock->quantity_on_hand,
                'counted_quantity' => $count, 'difference_quantity' => (string) BigDecimal::of($count)->minus($stock->quantity_on_hand),
                'notes' => $line['notes'] ?? null,
            ]);
        }
    }

    private function locked(StockAdjustment $record): StockAdjustment
    {
        return StockAdjustment::whereKey($record->id)->lockForUpdate()->firstOrFail();
    }

    private function requireStatus(StockAdjustment $record, array $allowed): void
    {
        if (! in_array($record->status, $allowed, true)) {
            throw ValidationException::withMessages(['status' => 'This operation is not allowed for the current adjustment status.']);
        }
    }
}
