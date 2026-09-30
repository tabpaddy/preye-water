<?php

namespace App\Filament\Resources\Suppliers;

use App\Models\Supplier;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';

    public static function form(Schema $schema): Schema
    {
        return Schemas\SupplierForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\SupplierInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\SuppliersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSuppliers::route('/'), 'create' => Pages\CreateSupplier::route('/create'), 'view' => Pages\ViewSupplier::route('/{record}'), 'edit' => Pages\EditSupplier::route('/{record}/edit')];
    }

    public static function getRelations(): array
    {
        return [RelationManagers\SupplierAddressesRelationManager::class, RelationManagers\PurchaseOrdersRelationManager::class, RelationManagers\SupplierInvoicesRelationManager::class, RelationManagers\SupplierPaymentsRelationManager::class];
    }
}
