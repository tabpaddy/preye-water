<?php

namespace App\Filament\Resources\PurchaseOrders\Tables;

use App\Enums\PurchaseOrderStatus;
use App\Filament\Support\InventoryFields;
use App\Filament\Support\ProcurementActions;
use App\Services\BusinessSettingService;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('purchase_order_number')->searchable(),
            TextColumn::make('supplier.name')->searchable(),
            TextColumn::make('order_date')->date(fn () => app(BusinessSettingService::class)->get()->date_format),
            TextColumn::make('expected_delivery_date')->date(fn () => app(BusinessSettingService::class)->get()->date_format),
            TextColumn::make('status')->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? str_replace('_', ' ', $state->value) : $state)->badge(),
            TextColumn::make('total_amount'),
            TextColumn::make('creator.staff_number'),
            TextColumn::make('approver.staff_number'),
        ])->filters([SelectFilter::make('supplier_id')->relationship('supplier', 'name')->searchable(), SelectFilter::make('status')->options(InventoryFields::options(PurchaseOrderStatus::class)), ProcurementActions::dates('order_date')])->recordActions([ViewAction::make(), EditAction::make(), ...ProcurementActions::purchaseOrder()])->defaultSort('id', 'desc');
    }
}
