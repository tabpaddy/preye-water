<?php

namespace App\Filament\Resources\InventoryLocations\Schemas;

use App\Enums\InventoryLocationType;
use App\Filament\Support\InventoryFields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class InventoryLocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->required()->maxLength(255),
            TextInput::make('name')->required()->maxLength(255),
            Select::make('location_type')->options(InventoryFields::options(InventoryLocationType::class))->required(),
            Textarea::make('address')->maxLength(5000), Toggle::make('is_active')->default(true),
        ])->columns(2);
    }
}
