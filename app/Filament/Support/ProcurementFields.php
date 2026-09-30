<?php

namespace App\Filament\Support;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class ProcurementFields
{
    public static function decimal(string $name, string $default = '0'): TextInput
    {
        return TextInput::make($name)->inputMode('decimal')->rules(['numeric'])->required(! in_array($name, ['discount_amount', 'tax_amount', 'other_cost', 'other_amount']))->default($default);
    }

    public static function supplier(bool $historical = false): Select
    {
        return Select::make('supplier_id')->required()->searchable()->live()
            ->getSearchResultsUsing(fn(string $search) => Supplier::query()->when($historical, fn($q) => $q->withTrashed(), fn($q) => $q->where('status', 'ACTIVE'))->where(fn($q) => $q->where('name', 'like', "%$search%")->orWhere('supplier_number', 'like', "%$search%"))->limit(50)->pluck('name', 'id'))
            ->getOptionLabelUsing(fn($value) => Supplier::withTrashed()->find($value)?->name)
            ->afterStateUpdated(function (Set $set) {
                $set('purchase_order_id', null);
                $set('items', []);
            });
    }

    public static function order(bool $receiving = false): Select
    {
        $select = Select::make('purchase_order_id')->label('Purchase order')->searchable()->live()->required($receiving)
            ->options(fn(Get $get) => PurchaseOrder::where('supplier_id', $get('supplier_id'))->when($receiving, fn($q) => $q->whereIn('status', ['APPROVED', 'PARTIALLY_RECEIVED']))->latest('id')->limit(100)->pluck('purchase_order_number', 'id'));
        if ($receiving) {
            $select->afterStateUpdated(fn($state, Set $set) => $set('items', self::remainingItems($state)));
        }

        return $select;
    }

    public static function remainingItems(mixed $id): array
    {
        return PurchaseOrderItem::where('purchase_order_id', $id)->whereColumn('quantity_received', '<', 'quantity_ordered')->get()->map(fn($line) => [
            'purchase_order_item_id' => $line->id,
            'ordered' => $line->quantity_ordered,
            'previously_received' => $line->quantity_received,
            'remaining' => $line->remaining_quantity,
            'quantity_received' => $line->remaining_quantity,
            'quantity_accepted' => $line->remaining_quantity,
            'quantity_rejected' => '0.000',
            'unit_cost' => $line->unit_cost,
        ])->all();
    }

    public static function poLine(bool $required = true): Select
    {
        return Select::make('purchase_order_item_id')->label('PO item')->required($required)->searchable()
            ->options(fn(Get $get) => PurchaseOrderItem::where('purchase_order_id', $get('../../purchase_order_id'))->get()->mapWithKeys(fn($line) => [$line->id => $line->sku . ' - ' . $line->item_name . ' (remaining ' . $line->remaining_quantity . ')'])->all());
    }

    public static function supplierAddressSchema(): array
    {
        $fields = [];
        foreach (['label', 'address_line_1', 'address_line_2', 'landmark', 'city', 'lga', 'state', 'country', 'postal_code'] as $field) {
            $input = TextInput::make($field)->maxLength(255)->required(in_array($field, ['address_line_1', 'city', 'state', 'country']));
            if ($field === 'country') {
                $input->default('Nigeria');
            }
            $fields[] = $input;
        }
        $fields[] = Toggle::make('is_default')->default(false);
        $fields[] = Toggle::make('is_active')->default(true);

        return $fields;
    }
}
