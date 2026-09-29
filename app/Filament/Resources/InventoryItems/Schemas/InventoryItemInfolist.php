<?php

namespace App\Filament\Resources\InventoryItems\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class InventoryItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('sku'),
            TextEntry::make('name'),
            TextEntry::make('item_type')->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? str_replace('_', ' ', $state->value) : $state),
            TextEntry::make('unit_of_measure')->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? str_replace('_', ' ', $state->value) : $state),
            TextEntry::make('reorder_level'),
            TextEntry::make('is_stock_tracked')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
            TextEntry::make('is_active')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
        ])->columns(3);
    }
}
