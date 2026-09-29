<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Support\InventoryFields;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            InventoryFields::item('inventory_item_id', true)->disabledOn('edit')->dehydrated(),
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('slug')->maxLength(255)->helperText('Leave blank to generate a unique URL name.'),
            Textarea::make('description')->maxLength(10000),
            Toggle::make('is_featured')->default(false),
            Toggle::make('is_available_online')->default(true), Toggle::make('is_active')->default(true),
        ])->columns(2);
    }
}
