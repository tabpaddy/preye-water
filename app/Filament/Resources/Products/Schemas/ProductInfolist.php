<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            ImageEntry::make('image_path')->disk('public'),
            TextEntry::make('name'),
            TextEntry::make('description'),
            TextEntry::make('inventoryItem.sku'),
            TextEntry::make('slug'),
            TextEntry::make('is_featured')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
            TextEntry::make('is_available_online')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
            TextEntry::make('is_active')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
        ])->columns(3);
    }
}
