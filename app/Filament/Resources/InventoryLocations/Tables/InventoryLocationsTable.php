<?php

namespace App\Filament\Resources\InventoryLocations\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class InventoryLocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->searchable(),
            TextColumn::make('name')->searchable(),
            TextColumn::make('location_type')->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? str_replace('_', ' ', $state->value) : $state)->badge(),
            TextColumn::make('is_active')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
        ])->filters([TernaryFilter::make('is_active')])->recordActions([ViewAction::make(), EditAction::make()])->defaultSort('id', 'desc');
    }
}
