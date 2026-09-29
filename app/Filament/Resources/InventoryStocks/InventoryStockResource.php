<?php

namespace App\Filament\Resources\InventoryStocks;

use App\Models\InventoryStock;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryStockResource extends Resource
{
    protected static ?string $model = InventoryStock::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\InventoryStockInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\InventoryStocksTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            0 => 'inventoryItem',
            1 => 'inventoryLocation',
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListInventoryStocks::route('/'), 'view' => Pages\ViewInventoryStock::route('/{record}')];
    }
}
