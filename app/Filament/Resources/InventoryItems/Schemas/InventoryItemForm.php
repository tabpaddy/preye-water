<?php

namespace App\Filament\Resources\InventoryItems\Schemas;

use App\Enums\InventoryItemType;
use App\Enums\UnitOfMeasure;
use App\Filament\Support\InventoryFields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class InventoryItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('sku')->required()->maxLength(255),
            TextInput::make('name')->required()->maxLength(255),
            Textarea::make('description')->maxLength(10000),
            Select::make('item_type')->options(InventoryFields::options(InventoryItemType::class))->required(),
            Select::make('unit_of_measure')->options(InventoryFields::options(UnitOfMeasure::class))->required(),
            TextInput::make('reorder_level')->inputMode('decimal')->rules(['numeric'])->minValue(0)->step('0.001'),
            Toggle::make('is_stock_tracked')->default(true), Toggle::make('is_active')->default(true),
        ])->columns(2);
    }
}
