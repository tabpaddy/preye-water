<?php

namespace App\Filament\Resources\Products\Tables;

use App\Services\ProductService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('inventoryItem.sku')->searchable(),
            TextColumn::make('slug')->searchable(),
            TextColumn::make('is_featured')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
            TextColumn::make('is_available_online')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
            TextColumn::make('is_active')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
        ])->filters([TernaryFilter::make('is_active')])->recordActions([ViewAction::make(), EditAction::make(), Action::make('archive')->color('danger')->requiresConfirmation()->visible(fn () => auth('staff')->user()->can('archive products'))->action(fn ($record) => app(ProductService::class)->archive($record, auth('staff')->user()))])->defaultSort('id', 'desc');
    }
}
