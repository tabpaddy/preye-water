<?php

namespace App\Filament\Resources\LeaveTypes\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LeaveTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required(), TextInput::make('code')->required()->maxLength(50), Textarea::make('description'), Toggle::make('is_paid'), Toggle::make('requires_approval')->default(true), Toggle::make('is_active')->default(true)]);
    }
}
