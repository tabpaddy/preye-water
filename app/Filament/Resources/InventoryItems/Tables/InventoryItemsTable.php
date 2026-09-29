<?php

namespace App\Filament\Resources\InventoryItems\Tables;

use App\Services\InventoryItemService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class InventoryItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('sku')->searchable(),
            TextColumn::make('name')->searchable(),
            TextColumn::make('item_type')->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? str_replace('_', ' ', $state->value) : $state)->badge(),
            TextColumn::make('unit_of_measure')->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? str_replace('_', ' ', $state->value) : $state)->badge(),
            TextColumn::make('reorder_level'),
            TextColumn::make('is_stock_tracked')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
            TextColumn::make('is_active')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
            TextColumn::make('product.name')->label('Sellable product')->placeholder('Not a product'),
        ])->filters([TernaryFilter::make('is_active')])->recordActions([ViewAction::make(), EditAction::make(), Action::make('archive')->color('danger')->requiresConfirmation()->visible(fn () => auth('staff')->user()->can('archive inventory items'))->action(fn ($record) => app(InventoryItemService::class)->archive($record, auth('staff')->user()))])->defaultSort('id', 'desc');
    }
}
