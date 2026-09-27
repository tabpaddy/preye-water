<?php

namespace App\Filament\Resources\SystemSettings\Schemas;

use App\Enums\SystemSettingType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SystemSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('group')->required()->maxLength(100)->disabledOn('edit')->dehydrated(),
                TextInput::make('key')->required()->maxLength(100)->disabledOn('edit')->dehydrated(),
                Select::make('type')->options(array_column(SystemSettingType::cases(), 'value', 'value'))->required(),
                Textarea::make('value')->helperText('Boolean: 0 or 1. JSON: valid JSON. Secrets must be configured in the environment.'),
                Toggle::make('is_public')->default(false),
                Textarea::make('description')->maxLength(2000),
            ]);
    }
}
