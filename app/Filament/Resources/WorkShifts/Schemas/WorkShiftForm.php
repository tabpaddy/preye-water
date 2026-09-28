<?php

namespace App\Filament\Resources\WorkShifts\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WorkShiftForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required(), TextInput::make('code')->required()->maxLength(50), TextInput::make('start_time')->type('time')->required(), TextInput::make('end_time')->type('time')->required(), TextInput::make('grace_period_minutes')->numeric()->default(0)->required(), TextInput::make('break_minutes')->numeric()->default(0)->required(), Toggle::make('is_overnight'), Toggle::make('is_active')->default(true)]);
    }
}
