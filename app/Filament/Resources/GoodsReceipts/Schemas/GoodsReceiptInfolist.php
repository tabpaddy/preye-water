<?php

namespace App\Filament\Resources\GoodsReceipts\Schemas;

use App\Services\BusinessSettingService;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class GoodsReceiptInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('goods_receipt_number'),
            TextEntry::make('supplier.name'),
            TextEntry::make('purchaseOrder.purchase_order_number'),
            TextEntry::make('inventoryLocation.name'),
            TextEntry::make('status')->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? str_replace('_', ' ', $state->value) : $state),
            TextEntry::make('received_at')->dateTime(fn () => app(BusinessSettingService::class)->dateTimeFormat())->timezone(fn () => app(BusinessSettingService::class)->get()->timezone),
            TextEntry::make('receiver.staff_number'),
            TextEntry::make('poster.staff_number'),
            TextEntry::make('notes'),
            RepeatableEntry::make('items')->schema([TextEntry::make('inventoryItem.name'), TextEntry::make('purchaseOrderItem.quantity_ordered'), TextEntry::make('purchaseOrderItem.quantity_received'), TextEntry::make('purchaseOrderItem.remaining_quantity'), TextEntry::make('quantity_received'), TextEntry::make('quantity_accepted'), TextEntry::make('quantity_rejected'), TextEntry::make('unit_cost'), TextEntry::make('rejection_reason')])->columns(3)->columnSpanFull(),
        ])->columns(3);
    }
}
