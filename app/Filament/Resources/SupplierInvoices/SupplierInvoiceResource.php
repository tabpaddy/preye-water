<?php

namespace App\Filament\Resources\SupplierInvoices;

use App\Models\SupplierInvoice;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupplierInvoiceResource extends Resource
{
    protected static ?string $model = SupplierInvoice::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';

    public static function form(Schema $schema): Schema
    {
        return Schemas\SupplierInvoiceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\SupplierInvoiceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\SupplierInvoicesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            0 => 'supplier',
            1 => 'purchaseOrder',
            2 => 'items',
            3 => 'allocations',
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSupplierInvoices::route('/'), 'create' => Pages\CreateSupplierInvoice::route('/create'), 'view' => Pages\ViewSupplierInvoice::route('/{record}'), 'edit' => Pages\EditSupplierInvoice::route('/{record}/edit')];
    }
}
