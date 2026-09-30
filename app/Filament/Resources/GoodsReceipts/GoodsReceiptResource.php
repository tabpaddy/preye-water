<?php

namespace App\Filament\Resources\GoodsReceipts;

use App\Models\GoodsReceipt;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GoodsReceiptResource extends Resource
{
    protected static ?string $model = GoodsReceipt::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';

    public static function form(Schema $schema): Schema
    {
        return Schemas\GoodsReceiptForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\GoodsReceiptInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\GoodsReceiptsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            0 => 'supplier',
            1 => 'purchaseOrder',
            2 => 'inventoryLocation',
            3 => 'receiver',
            4 => 'inspector',
            5 => 'poster',
            6 => 'items.inventoryItem',
            7 => 'items.purchaseOrderItem',
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListGoodsReceipts::route('/'), 'create' => Pages\CreateGoodsReceipt::route('/create'), 'view' => Pages\ViewGoodsReceipt::route('/{record}'), 'edit' => Pages\EditGoodsReceipt::route('/{record}/edit')];
    }
}
