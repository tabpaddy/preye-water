<?php

namespace App\Services;

use App\Enums\InventoryItemType;
use App\Enums\UnitOfMeasure;
use App\Models\InventoryItem;
use App\Models\Staff;
use App\Support\InventoryDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InventoryItemService
{
    public function __construct(private ActivityLogService $audit) {}

    public function create(array $data, Staff $actor): InventoryItem
    {
        Gate::forUser($actor)->authorize('create inventory items');

        return $this->save(new InventoryItem, $data, $actor);
    }

    public function update(InventoryItem $item, array $data, Staff $actor): InventoryItem
    {
        Gate::forUser($actor)->authorize('update inventory items');

        return $this->save($item, $data, $actor);
    }

    private function save(InventoryItem $item, array $data, Staff $actor): InventoryItem
    {
        return DB::transaction(function () use ($item, $data, $actor) {
            if ($item->exists) {
                $item = InventoryItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            }
            $data = Validator::make($data, [
                'sku' => ['required', 'string', 'max:255', Rule::unique('inventory_items', 'sku')->ignore($item->id)],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:10000'],
                'item_type' => ['required', Rule::enum(InventoryItemType::class)],
                'unit_of_measure' => ['required', Rule::enum(UnitOfMeasure::class)],
                'reorder_level' => ['nullable'],
                'is_stock_tracked' => ['sometimes', 'boolean'], 'is_active' => ['sometimes', 'boolean'],
            ])->validate();
            if (isset($data['reorder_level'])) {
                $data['reorder_level'] = InventoryDecimal::value($data['reorder_level'], 3, false, 'reorder_level');
            }
            if ($item->exists) {
                if ($item->product()->withTrashed()->exists() && $data['item_type'] !== InventoryItemType::FINISHED_GOOD->value && $data['item_type'] !== InventoryItemType::FINISHED_GOOD) {
                    throw ValidationException::withMessages(['item_type' => 'A product must remain a finished good.']);
                }
                if (($item->movements()->exists() || $item->stocks()->exists()) && (! in_array($data['unit_of_measure'], [$item->unit_of_measure, $item->unit_of_measure->value], true) || (isset($data['is_stock_tracked']) && (bool) $data['is_stock_tracked'] !== $item->is_stock_tracked))) {
                    throw ValidationException::withMessages(['unit_of_measure' => 'Units and stock tracking cannot change after stock records exist.']);
                }
            }
            $old = $item->toArray();
            $event = $item->exists ? 'updated' : 'created';
            $item->fill($data)->save();
            $this->audit->record('inventory_item.'.$event, $item, $actor, 'Inventory item '.$event, $old, $item->toArray());

            return $item;
        });
    }

    public function archive(InventoryItem $item, Staff $actor): void
    {
        Gate::forUser($actor)->authorize('archive inventory items');
        DB::transaction(function () use ($item, $actor) {
            $item = InventoryItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            if ($item->stocks()->where(fn ($q) => $q->where('quantity_on_hand', '>', 0)->orWhere('quantity_reserved', '>', 0))->exists() || $item->product()->exists()) {
                throw ValidationException::withMessages(['inventory_item' => 'Empty stock and archive the linked product first.']);
            }
            $item->delete();
            $this->audit->record('inventory_item.archived', $item, $actor, 'Inventory item archived');
        });
    }
}
