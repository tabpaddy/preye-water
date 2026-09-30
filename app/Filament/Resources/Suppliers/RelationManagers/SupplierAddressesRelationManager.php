<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use App\Filament\Support\ProcurementFields;
use App\Services\SupplierService;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplierAddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(ProcurementFields::supplierAddressSchema());
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('label'), TextColumn::make('address_line_1'), TextColumn::make('city'), TextColumn::make('state'),
            TextColumn::make('is_default')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
            TextColumn::make('is_active')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
        ])->headerActions([
            CreateAction::make()->using(fn (array $data) => app(SupplierService::class)->saveAddress($this->getOwnerRecord(), $data, auth('staff')->user())),
        ])->recordActions([
            EditAction::make()->using(fn ($record, array $data) => app(SupplierService::class)->saveAddress($this->getOwnerRecord(), $data, auth('staff')->user(), $record)),
        ]);
    }
}
