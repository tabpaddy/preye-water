<?php

namespace App\Filament\Resources\SupplierPayments;

use App\Models\SupplierPayment;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupplierPaymentResource extends Resource
{
    protected static ?string $model = SupplierPayment::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';

    public static function form(Schema $schema): Schema
    {
        return Schemas\SupplierPaymentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\SupplierPaymentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\SupplierPaymentsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            0 => 'supplier',
            1 => 'recorder',
            2 => 'approver',
            3 => 'allocations.supplierInvoice',
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSupplierPayments::route('/'), 'create' => Pages\CreateSupplierPayment::route('/create'), 'view' => Pages\ViewSupplierPayment::route('/{record}')];
    }
}
