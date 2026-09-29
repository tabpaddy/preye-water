<?php

namespace App\Filament\Resources\StockAdjustments\Schemas;

use App\Enums\StockAdjustmentReason;
use App\Filament\Support\InventoryFields;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StockAdjustmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            InventoryFields::location(),
            Select::make('reason')->options(InventoryFields::options(StockAdjustmentReason::class))->required(),
            Textarea::make('notes')->maxLength(5000),
            Repeater::make('items')->schema([
                InventoryFields::item(),
                TextInput::make('counted_quantity')->required()->inputMode('decimal')->rules(['numeric'])->minValue(0)->step('0.001'),
                Textarea::make('notes')->maxLength(5000),
            ])->minItems(1)->maxItems(200)->columns(3)->columnSpanFull()
                ->helperText('Saving a draft captures current system quantities. Re-saving is a recount. Submitted counts are frozen.'),
        ])->columns(2);
    }
}
