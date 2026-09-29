<?php

namespace App\Filament\Resources\StockMovements\Tables;

use App\Enums\StockMovementType;
use App\Filament\Support\InventoryFields;
use App\Services\BusinessSettingService;
use Carbon\CarbonImmutable;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('uuid'),
            TextColumn::make('inventoryItem.name')->searchable(),
            TextColumn::make('movement_type')->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? str_replace('_', ' ', $state->value) : $state)->badge(),
            TextColumn::make('fromLocation.name')->searchable(),
            TextColumn::make('toLocation.name')->searchable(),
            TextColumn::make('quantity'),
            TextColumn::make('unit_cost'),
            TextColumn::make('total_cost'),
            TextColumn::make('reference_number')->searchable(),
            TextColumn::make('performer.staff_number'),
            TextColumn::make('occurred_at')->dateTime()->timezone(fn () => app(BusinessSettingService::class)->get()->timezone),
        ])->filters([SelectFilter::make('inventory_item_id')->relationship('inventoryItem', 'name')->searchable(),
            SelectFilter::make('from_location_id')->relationship('fromLocation', 'name')->searchable(),
            SelectFilter::make('to_location_id')->relationship('toLocation', 'name')->searchable(),
            SelectFilter::make('performed_by')->relationship('performer', 'staff_number')->searchable(),
            SelectFilter::make('movement_type')->options(InventoryFields::options(StockMovementType::class)),
            Filter::make('dates')->schema([
                DatePicker::make('from'), DatePicker::make('until'),
            ])->query(fn ($query, array $data) => $query->when($data['from'] ?? null, fn ($q, $date) => $q->where('occurred_at', '>=', CarbonImmutable::parse($date, app(BusinessSettingService::class)->get()->timezone)->startOfDay()->utc()))->when($data['until'] ?? null, fn ($q, $date) => $q->where('occurred_at', '<=', CarbonImmutable::parse($date, app(BusinessSettingService::class)->get()->timezone)->endOfDay()->utc())))])->recordActions([ViewAction::make()])->defaultSort('id', 'desc');
    }
}
