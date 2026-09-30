<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Filament\Support\InventoryFields;
use App\Filament\Support\ProcurementFields;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            ProcurementFields::supplier()->disabledOn('edit')->dehydrated(),
            DatePicker::make('order_date')->required()->default(now()),
            DatePicker::make('expected_delivery_date'),
            TextInput::make('supplier_reference')->maxLength(255),
            ProcurementFields::decimal('discount_amount')->label('Order discount'),
            ProcurementFields::decimal('tax_amount')->label('Additional order tax'),
            ProcurementFields::decimal('other_cost'),
            Textarea::make('notes')->maxLength(5000),
            Repeater::make('items')->schema([
                InventoryFields::item(), ProcurementFields::decimal('quantity_ordered', '1'), ProcurementFields::decimal('unit_cost'),
                ProcurementFields::decimal('discount_amount')->label('Line discount'), ProcurementFields::decimal('tax_amount')->label('Line tax'),
                TextInput::make('line_total')->disabled()->dehydrated(false)->helperText('Calculated when saved.'),
            ])->minItems(1)->maxItems(200)->columns(3)->columnSpanFull(),
        ])->columns(2);
    }
}
