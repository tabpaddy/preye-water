<?php

namespace App\Filament\Resources\GoodsReceipts\Schemas;

use App\Filament\Support\InventoryFields;
use App\Filament\Support\ProcurementFields;
use App\Services\BusinessSettingService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GoodsReceiptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            ProcurementFields::supplier()->disabledOn('edit')->dehydrated(),
            ProcurementFields::order(true)->disabledOn('edit')->dehydrated(),
            InventoryFields::location(),
            TextInput::make('supplier_delivery_note')->maxLength(255),
            DateTimePicker::make('received_at')->required()->default(now())->timezone(fn () => app(BusinessSettingService::class)->get()->timezone),
            Textarea::make('notes')->maxLength(5000),
            Repeater::make('items')->schema([
                ProcurementFields::poLine(),
                TextInput::make('ordered')->disabled()->dehydrated(false),
                TextInput::make('previously_received')->disabled()->dehydrated(false),
                TextInput::make('remaining')->disabled()->dehydrated(false),
                ProcurementFields::decimal('quantity_received', '1'), ProcurementFields::decimal('quantity_accepted', '1'),
                ProcurementFields::decimal('quantity_rejected'), ProcurementFields::decimal('unit_cost'),
                Textarea::make('rejection_reason')->maxLength(5000),
            ])->minItems(1)->maxItems(200)->columns(3)->columnSpanFull()
                ->helperText('Select the PO to load remaining quantities. Record the actual delivery and inspection split before confirming inspection.'),
        ])->columns(2);
    }
}
