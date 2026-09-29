<?php

namespace App\Filament\Support;

use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use Filament\Forms\Components\Select;

class InventoryFields
{
    public static function options(string $enum): array
    {
        $options = [];
        foreach ($enum::cases() as $case) {
            $options[$case->value] = ucwords(strtolower(str_replace('_', ' ', $case->value)));
        }

        return $options;
    }

    public static function item(string $field = 'inventory_item_id', bool $finishedOnly = false): Select
    {
        return Select::make($field)->label('Inventory item')->required()->searchable()
            ->helperText($finishedOnly ? 'Select an existing finished good from Inventory Items. This does not add stock.' : null)
            ->getSearchResultsUsing(fn (string $search) => InventoryItem::active()->when($finishedOnly, fn ($q) => $q->finishedGoods())->where(fn ($q) => $q->where('name', 'like', "%$search%")->orWhere('sku', 'like', "%$search%"))->limit(50)->get()->mapWithKeys(fn ($i) => [$i->id => $i->sku.' - '.$i->name])->all())
            ->getOptionLabelUsing(fn ($value) => InventoryItem::withTrashed()->find($value)?->name);
    }

    public static function location(string $field = 'inventory_location_id'): Select
    {
        return Select::make($field)->label('Location')->required()->searchable()
            ->options(fn () => InventoryLocation::where('is_active', true)->orderBy('name')->pluck('name', 'id'));
    }
}
