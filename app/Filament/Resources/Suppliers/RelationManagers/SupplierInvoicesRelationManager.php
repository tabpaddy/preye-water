<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use App\Filament\Resources\SupplierInvoices\SupplierInvoiceResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SupplierInvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth('staff')->user()->can('view supplier invoices');
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('internal_reference')->searchable(), TextColumn::make('total_amount')])->recordUrl(fn ($record) => SupplierInvoiceResource::getUrl('view', ['record' => $record]));
    }
}
