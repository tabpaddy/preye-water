<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Enums\PriceType;
use App\Filament\Support\InventoryFields;
use App\Models\ProductPrice;
use App\Services\BusinessSettingService;
use App\Services\PricingService;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProductPricesRelationManager extends RelationManager
{
    protected static string $relationship = 'prices';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth('staff')->user()->can('view product prices');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        $timezone = app(BusinessSettingService::class)->get()->timezone;

        return $schema->components([
            Select::make('price_type')->options(InventoryFields::options(PriceType::class))->required(),
            TextInput::make('amount')->inputMode('decimal')->rules(['numeric'])->minValue('0.01')->step('0.01')->required(),
            TextInput::make('minimum_quantity')->inputMode('decimal')->rules(['numeric'])->minValue('0.001')->step('0.001')->default('1.000')->required(),
            DateTimePicker::make('effective_from')->timezone($timezone),
            DateTimePicker::make('effective_until')->timezone($timezone),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('price_type')->formatStateUsing(fn (PriceType $state) => $state->value),
            TextColumn::make('amount'), TextColumn::make('minimum_quantity'),
            TextColumn::make('effective_from')->dateTime()->timezone(fn () => app(BusinessSettingService::class)->get()->timezone),
            TextColumn::make('effective_until')->dateTime()->timezone(fn () => app(BusinessSettingService::class)->get()->timezone),
            TextColumn::make('is_active')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
            TextColumn::make('currently_effective')->state(fn (ProductPrice $record) => $record->is_active && (! $record->effective_from || $record->effective_from->lte(now())) && (! $record->effective_until || $record->effective_until->gte(now())) ? 'Current tier' : 'Outside current window'),
        ])->headerActions([
            CreateAction::make()->using(fn (array $data) => app(PricingService::class)->create($this->getOwnerRecord(), $data, auth('staff')->user())),
        ])->recordActions([
            EditAction::make()->using(fn (ProductPrice $record, array $data) => app(PricingService::class)->update($record, $data, auth('staff')->user())),
        ]);
    }
}
