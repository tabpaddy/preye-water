<?php

namespace App\Filament\Resources\InventoryStocks\Schemas;

use App\Enums\UnitOfMeasure;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class InventoryStockInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('inventoryItem.name'),
            TextEntry::make('inventoryItem.sku'),
            TextEntry::make('inventoryLocation.name'),
            TextEntry::make('inventoryItem.unit_of_measure')->label('Unit')->formatStateUsing(fn (UnitOfMeasure $state) => $state->value),
            TextEntry::make('quantity_on_hand'),
            TextEntry::make('quantity_reserved'),
            TextEntry::make('available_quantity'),
            TextEntry::make('average_unit_cost'),
            TextEntry::make('estimated_value'),
        ])->columns(3);
    }
}
