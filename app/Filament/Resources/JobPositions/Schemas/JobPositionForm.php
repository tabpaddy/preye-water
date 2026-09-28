<?php

namespace App\Filament\Resources\JobPositions\Schemas;

use App\Models\Department;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class JobPositionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([Select::make('department_id')->options(fn () => Department::pluck('name', 'id'))->required()->searchable(), TextInput::make('code')->required()->maxLength(50), TextInput::make('name')->required()->maxLength(255), Textarea::make('description'), Toggle::make('is_active')->default(true)]);
    }
}
