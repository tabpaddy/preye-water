<?php

namespace App\Filament\Resources\StockAdjustments;

use App\Models\StockAdjustment;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockAdjustmentResource extends Resource
{
    protected static ?string $model = StockAdjustment::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    public static function form(Schema $schema): Schema
    {
        return Schemas\StockAdjustmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\StockAdjustmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\StockAdjustmentsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            0 => 'inventoryLocation',
            1 => 'creator',
            2 => 'approver',
            3 => 'items.inventoryItem',
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListStockAdjustments::route('/'), 'create' => Pages\CreateStockAdjustment::route('/create'), 'view' => Pages\ViewStockAdjustment::route('/{record}'), 'edit' => Pages\EditStockAdjustment::route('/{record}/edit')];
    }
}
