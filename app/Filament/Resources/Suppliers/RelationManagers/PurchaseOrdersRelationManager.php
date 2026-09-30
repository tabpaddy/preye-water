<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseOrders';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth('staff')->user()->can('view purchase orders');
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('purchase_order_number')->searchable(), TextColumn::make('total_amount')])->recordUrl(fn ($record) => PurchaseOrderResource::getUrl('view', ['record' => $record]));
    }
}
