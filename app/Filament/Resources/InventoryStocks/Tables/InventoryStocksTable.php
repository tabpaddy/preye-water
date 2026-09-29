<?php

namespace App\Filament\Resources\InventoryStocks\Tables;

use App\Enums\UnitOfMeasure;
use App\Filament\Support\InventoryFields;
use App\Models\InventoryLocation;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InventoryStocksTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('inventoryItem.name')->searchable(),
            TextColumn::make('inventoryItem.sku')->searchable(),
            TextColumn::make('inventoryLocation.name')->searchable(),
            TextColumn::make('inventoryItem.unit_of_measure')->label('Unit')->formatStateUsing(fn (UnitOfMeasure $state) => $state->value),
            TextColumn::make('quantity_on_hand'),
            TextColumn::make('quantity_reserved'),
            TextColumn::make('available_quantity'),
            TextColumn::make('average_unit_cost'),
            TextColumn::make('estimated_value'),
        ])->filters([SelectFilter::make('inventory_location_id')->relationship('inventoryLocation', 'name')])->recordActions([ViewAction::make(), Action::make('transfer')->visible(fn () => auth('staff')->user()->can('transfer inventory'))->schema([
            InventoryFields::location('to_location_id'),
            TextInput::make('quantity')->required()->inputMode('decimal')->rules(['numeric'])->minValue('0.001')->step('0.001'),
        ])->requiresConfirmation()->action(fn ($record, array $data) => app(InventoryService::class)->transfer($record->inventoryItem, $record->inventoryLocation, InventoryLocation::findOrFail($data['to_location_id']), $data['quantity'], auth('staff')->user()))])->defaultSort('id', 'desc');
    }
}
