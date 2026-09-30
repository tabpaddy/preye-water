<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use App\Enums\SupplierStatus;
use App\Enums\SupplierType;
use App\Filament\Support\InventoryFields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            Select::make('supplier_type')->options(InventoryFields::options(SupplierType::class)),
            TextInput::make('contact_person')->maxLength(255),
            TextInput::make('phone')->maxLength(255), TextInput::make('alternate_phone')->maxLength(255),
            TextInput::make('email')->email()->maxLength(255), TextInput::make('tax_identification_number')->maxLength(255),
            TextInput::make('payment_terms_days')->integer()->minValue(0)->maxValue(3650),
            Select::make('status')->options(InventoryFields::options(SupplierStatus::class))->default('ACTIVE')->required(),
            Textarea::make('notes')->maxLength(5000),
        ])->columns(2);
    }
}
