<?php

namespace App\Filament\Resources\InventoryLocations;

use App\Models\InventoryLocation;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryLocationResource extends Resource
{
    protected static ?string $model = InventoryLocation::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    public static function form(Schema $schema): Schema
    {
        return Schemas\InventoryLocationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\InventoryLocationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\InventoryLocationsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListInventoryLocations::route('/'), 'create' => Pages\CreateInventoryLocation::route('/create'), 'view' => Pages\ViewInventoryLocation::route('/{record}'), 'edit' => Pages\EditInventoryLocation::route('/{record}/edit')];
    }
}
