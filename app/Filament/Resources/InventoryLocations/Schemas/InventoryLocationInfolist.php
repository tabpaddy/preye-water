<?php

namespace App\Filament\Resources\InventoryLocations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class InventoryLocationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('code'),
            TextEntry::make('name'),
            TextEntry::make('location_type')->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? str_replace('_', ' ', $state->value) : $state),
            TextEntry::make('is_active')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
        ])->columns(3);
    }
}
