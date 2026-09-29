<?php

namespace App\Filament\Resources\Products;

use App\Models\Product;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    public static function form(Schema $schema): Schema
    {
        return Schemas\ProductForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\ProductInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\ProductsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            0 => 'inventoryItem',
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListProducts::route('/'), 'create' => Pages\CreateProduct::route('/create'), 'view' => Pages\ViewProduct::route('/{record}'), 'edit' => Pages\EditProduct::route('/{record}/edit')];
    }

    public static function getRelations(): array
    {
        return [RelationManagers\ProductPricesRelationManager::class];
    }
}
