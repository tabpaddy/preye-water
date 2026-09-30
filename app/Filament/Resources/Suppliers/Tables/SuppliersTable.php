<?php

namespace App\Filament\Resources\Suppliers\Tables;

use App\Enums\SupplierStatus;
use App\Filament\Support\InventoryFields;
use App\Services\SupplierService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SuppliersTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('supplier_number')->searchable(),
            TextColumn::make('name')->searchable(),
            TextColumn::make('supplier_type')->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? str_replace('_', ' ', $state->value) : $state)->badge(),
            TextColumn::make('contact_person'),
            TextColumn::make('phone'),
            TextColumn::make('email'),
            TextColumn::make('status')->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? str_replace('_', ' ', $state->value) : $state)->badge(),
        ])->filters([SelectFilter::make('status')->options(InventoryFields::options(SupplierStatus::class))])->recordActions([ViewAction::make(), EditAction::make(), Action::make('archive')->requiresConfirmation()->color('danger')->visible(fn () => auth('staff')->user()->can('archive suppliers'))->action(fn ($record) => app(SupplierService::class)->archive($record, auth('staff')->user()))])->defaultSort('id', 'desc');
    }
}
