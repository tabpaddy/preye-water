<?php

namespace App\Filament\Resources\SupplierPayments\Schemas;

use App\Enums\SupplierPaymentMethod;
use App\Filament\Support\InventoryFields;
use App\Filament\Support\ProcurementFields;
use App\Services\BusinessSettingService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SupplierPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            ProcurementFields::supplier(true),
            Select::make('payment_method')->options(InventoryFields::options(SupplierPaymentMethod::class))->required(),
            ProcurementFields::decimal('amount'), TextInput::make('currency')->default('NGN')->readOnly()->required(),
            TextInput::make('reference')->maxLength(255),
            DateTimePicker::make('paid_at')->timezone(fn () => app(BusinessSettingService::class)->get()->timezone),
            Textarea::make('notes')->maxLength(5000),
        ])->columns(2);
    }
}
