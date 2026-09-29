<?php

namespace App\Filament\Resources\StockMovements;

use App\Models\StockMovement;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\StockMovementInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\StockMovementsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            0 => 'inventoryItem',
            1 => 'fromLocation',
            2 => 'toLocation',
            3 => 'performer',
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListStockMovements::route('/'), 'view' => Pages\ViewStockMovement::route('/{record}')];
    }
}
