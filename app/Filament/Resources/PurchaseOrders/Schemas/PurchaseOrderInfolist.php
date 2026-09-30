<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Services\BusinessSettingService;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PurchaseOrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('purchase_order_number'),
            TextEntry::make('supplier.name'),
            TextEntry::make('order_date')->date(fn () => app(BusinessSettingService::class)->get()->date_format),
            TextEntry::make('expected_delivery_date')->date(fn () => app(BusinessSettingService::class)->get()->date_format),
            TextEntry::make('status')->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? str_replace('_', ' ', $state->value) : $state),
            TextEntry::make('total_amount'),
            TextEntry::make('creator.staff_number'),
            TextEntry::make('approver.staff_number'),
            TextEntry::make('notes'),
            RepeatableEntry::make('items')->schema([TextEntry::make('item_name'), TextEntry::make('sku'), TextEntry::make('quantity_ordered'), TextEntry::make('quantity_received'), TextEntry::make('remaining_quantity'), TextEntry::make('unit_cost'), TextEntry::make('discount_amount'), TextEntry::make('tax_amount'), TextEntry::make('line_total')])->columns(3)->columnSpanFull(),
            TextEntry::make('subtotal'),
            TextEntry::make('discount_amount'),
            TextEntry::make('tax_amount'),
            TextEntry::make('other_cost'),
        ])->columns(3);
    }
}
