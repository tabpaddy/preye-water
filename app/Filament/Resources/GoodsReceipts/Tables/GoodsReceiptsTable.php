<?php

namespace App\Filament\Resources\GoodsReceipts\Tables;

use App\Enums\GoodsReceiptStatus;
use App\Filament\Support\InventoryFields;
use App\Filament\Support\ProcurementActions;
use App\Services\BusinessSettingService;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GoodsReceiptsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('goods_receipt_number')->searchable(),
            TextColumn::make('supplier.name')->searchable(),
            TextColumn::make('purchaseOrder.purchase_order_number'),
            TextColumn::make('inventoryLocation.name'),
            TextColumn::make('status')->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? str_replace('_', ' ', $state->value) : $state)->badge(),
            TextColumn::make('received_at')->dateTime(fn () => app(BusinessSettingService::class)->dateTimeFormat())->timezone(fn () => app(BusinessSettingService::class)->get()->timezone),
            TextColumn::make('receiver.staff_number'),
            TextColumn::make('poster.staff_number'),
        ])->filters([SelectFilter::make('supplier_id')->relationship('supplier', 'name')->searchable(), SelectFilter::make('status')->options(InventoryFields::options(GoodsReceiptStatus::class)), SelectFilter::make('purchase_order_id')->relationship('purchaseOrder', 'purchase_order_number')->searchable(), SelectFilter::make('inventory_location_id')->relationship('inventoryLocation', 'name'), ProcurementActions::dates('received_at')])->recordActions([ViewAction::make(), EditAction::make(), ...ProcurementActions::goodsReceipt()])->defaultSort('id', 'desc');
    }
}
