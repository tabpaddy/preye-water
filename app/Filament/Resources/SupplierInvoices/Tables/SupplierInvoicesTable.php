<?php

namespace App\Filament\Resources\SupplierInvoices\Tables;

use App\Enums\SupplierInvoicePaymentStatus;
use App\Filament\Support\InventoryFields;
use App\Filament\Support\ProcurementActions;
use App\Services\BusinessSettingService;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupplierInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('internal_reference')->searchable(),
            TextColumn::make('invoice_number')->searchable(),
            TextColumn::make('supplier.name')->searchable(),
            TextColumn::make('invoice_date')->date(fn () => app(BusinessSettingService::class)->get()->date_format),
            TextColumn::make('due_date')->date(fn () => app(BusinessSettingService::class)->get()->date_format),
            TextColumn::make('total_amount'),
            TextColumn::make('amount_paid'),
            TextColumn::make('amount_due'),
            TextColumn::make('payment_status')->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? str_replace('_', ' ', $state->value) : $state)->badge(),
        ])->filters([SelectFilter::make('supplier_id')->relationship('supplier', 'name')->searchable(), SelectFilter::make('payment_status')->options(InventoryFields::options(SupplierInvoicePaymentStatus::class)), ProcurementActions::dates('invoice_date'), ProcurementActions::dates('due_date')])->recordActions([ViewAction::make(), EditAction::make()])->defaultSort('id', 'desc');
    }
}
