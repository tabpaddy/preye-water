<?php

namespace App\Filament\Resources\SupplierPayments\Tables;

use App\Enums\SupplierPaymentStatus;
use App\Filament\Support\InventoryFields;
use App\Filament\Support\ProcurementActions;
use App\Services\BusinessSettingService;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupplierPaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('payment_number')->searchable(),
            TextColumn::make('supplier.name')->searchable(),
            TextColumn::make('payment_method')->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? str_replace('_', ' ', $state->value) : $state)->badge(),
            TextColumn::make('amount'),
            TextColumn::make('status')->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? str_replace('_', ' ', $state->value) : $state)->badge(),
            TextColumn::make('paid_at')->dateTime(fn () => app(BusinessSettingService::class)->dateTimeFormat())->timezone(fn () => app(BusinessSettingService::class)->get()->timezone),
            TextColumn::make('recorder.staff_number'),
            TextColumn::make('approver.staff_number'),
        ])->filters([SelectFilter::make('supplier_id')->relationship('supplier', 'name')->searchable(), SelectFilter::make('status')->options(InventoryFields::options(SupplierPaymentStatus::class)), ProcurementActions::dates('paid_at')])->recordActions([ViewAction::make(), ...ProcurementActions::payment()])->defaultSort('id', 'desc');
    }
}
