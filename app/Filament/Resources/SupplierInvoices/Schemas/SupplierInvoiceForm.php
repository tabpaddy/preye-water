<?php

namespace App\Filament\Resources\SupplierInvoices\Schemas;

use App\Filament\Support\ProcurementFields;
use App\Models\InventoryItem;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SupplierInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            ProcurementFields::supplier()->disabledOn('edit')->dehydrated(),
            ProcurementFields::order(), TextInput::make('invoice_number')->required()->maxLength(255),
            DatePicker::make('invoice_date')->required()->default(now()), DatePicker::make('due_date'),
            ProcurementFields::decimal('discount_amount')->label('Invoice discount'), ProcurementFields::decimal('tax_amount')->label('Additional invoice tax'),
            ProcurementFields::decimal('other_amount'), Textarea::make('notes')->maxLength(5000),
            Repeater::make('items')->schema([
                ProcurementFields::poLine(false),
                Select::make('inventory_item_id')->label('Inventory item (optional)')->searchable()
                    ->getSearchResultsUsing(fn (string $search) => InventoryItem::where('name', 'like', "%$search%")->limit(50)->pluck('name', 'id'))
                    ->getOptionLabelUsing(fn ($value) => InventoryItem::withTrashed()->find($value)?->name),
                Textarea::make('description')->required()->maxLength(5000),
                ProcurementFields::decimal('quantity', '1'), ProcurementFields::decimal('unit_cost'),
                ProcurementFields::decimal('discount_amount')->label('Line discount'), ProcurementFields::decimal('tax_amount')->label('Line tax'),
                TextInput::make('line_total')->disabled()->dehydrated(false)->helperText('Calculated when saved.'),
            ])->minItems(1)->maxItems(200)->columns(3)->columnSpanFull(),
        ])->columns(2);
    }
}
