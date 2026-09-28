<?php

namespace App\Filament\Resources\StaffAttendances\Schemas;

use App\Enums\AttendanceStatus;
use App\Filament\Support\WorkforceFields;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StaffAttendanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([WorkforceFields::staff()->required(), DatePicker::make('attendance_date')->default(today())->required(), Select::make('status')->options(array_column(AttendanceStatus::cases(), 'value', 'value'))->default('PRESENT')->required(), TextInput::make('clock_in_at')->type('datetime-local')->helperText('Business local time'), TextInput::make('clock_out_at')->type('datetime-local')->helperText('Business local time'), Textarea::make('notes')]);
    }
}
