<?php

namespace App\Filament\Resources\Departments\Schemas;

use App\Filament\Support\WorkforceFields;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('code')->required()->maxLength(50), TextInput::make('name')->required()->maxLength(255), Textarea::make('description'), WorkforceFields::staff('manager_id'), Toggle::make('is_active')->default(true)]);
    }
}
