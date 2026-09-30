<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use App\Filament\Resources\SupplierPayments\SupplierPaymentResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SupplierPaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth('staff')->user()->can('view supplier payments');
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('payment_number')->searchable(), TextColumn::make('amount')])->recordUrl(fn ($record) => SupplierPaymentResource::getUrl('view', ['record' => $record]));
    }
}
