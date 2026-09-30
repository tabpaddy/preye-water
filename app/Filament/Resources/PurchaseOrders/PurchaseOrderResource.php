<?php

namespace App\Filament\Resources\PurchaseOrders;

use App\Models\PurchaseOrder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';

    public static function form(Schema $schema): Schema
    {
        return Schemas\PurchaseOrderForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\PurchaseOrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\PurchaseOrdersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            0 => 'supplier',
            1 => 'creator',
            2 => 'approver',
            3 => 'items',
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListPurchaseOrders::route('/'), 'create' => Pages\CreatePurchaseOrder::route('/create'), 'view' => Pages\ViewPurchaseOrder::route('/{record}'), 'edit' => Pages\EditPurchaseOrder::route('/{record}/edit')];
    }
}
