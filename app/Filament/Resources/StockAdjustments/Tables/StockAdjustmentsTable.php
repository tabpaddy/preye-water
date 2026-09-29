<?php

namespace App\Filament\Resources\StockAdjustments\Tables;

use App\Enums\StockAdjustmentStatus;
use App\Filament\Support\InventoryFields;
use App\Filament\Support\StockAdjustmentActions;
use App\Services\BusinessSettingService;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockAdjustmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('adjustment_number')->searchable(),
            TextColumn::make('inventoryLocation.name')->searchable(),
            TextColumn::make('reason')->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? str_replace('_', ' ', $state->value) : $state)->badge(),
            TextColumn::make('status')->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? str_replace('_', ' ', $state->value) : $state)->badge(),
            TextColumn::make('creator.staff_number'),
            TextColumn::make('approver.staff_number'),
            TextColumn::make('posted_at')->dateTime()->timezone(fn () => app(BusinessSettingService::class)->get()->timezone),
        ])->filters([SelectFilter::make('inventory_location_id')->relationship('inventoryLocation', 'name'),
            SelectFilter::make('status')->options(InventoryFields::options(StockAdjustmentStatus::class))])->recordActions([ViewAction::make(), EditAction::make(), ...StockAdjustmentActions::make()])->defaultSort('id', 'desc');
    }
}
