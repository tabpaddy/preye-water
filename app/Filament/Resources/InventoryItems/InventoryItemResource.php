<?php

namespace App\Filament\Resources\InventoryItems;

use App\Models\InventoryItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryItemResource extends Resource
{
    protected static ?string $model = InventoryItem::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    public static function form(Schema $schema): Schema
    {
        return Schemas\InventoryItemForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\InventoryItemInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\InventoryItemsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            0 => 'product',
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListInventoryItems::route('/'), 'create' => Pages\CreateInventoryItem::route('/create'), 'view' => Pages\ViewInventoryItem::route('/{record}'), 'edit' => Pages\EditInventoryItem::route('/{record}/edit')];
    }
}
