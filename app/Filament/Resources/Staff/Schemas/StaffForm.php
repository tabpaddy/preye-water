<?php

namespace App\Filament\Resources\Staff\Schemas;

use App\Enums\StaffStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class StaffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')->required()->maxLength(255),
                TextInput::make('middle_name')->maxLength(255),
                TextInput::make('last_name')->required()->maxLength(255),
                TextInput::make('email')->email()->maxLength(255),
                TextInput::make('phone')->tel()->maxLength(255),
                TextInput::make('alternate_phone')->tel()->maxLength(255),
                Select::make('gender')->options(['male' => 'Male', 'female' => 'Female', 'other' => 'Other']),
                DatePicker::make('date_of_birth')->maxDate(today()),
                TextInput::make('password')->password()->autocomplete('new-password')->required(fn (string $operation) => $operation === 'create')->minLength(12)->confirmed()->dehydrated(fn ($state) => filled($state)),
                TextInput::make('password_confirmation')->password()->autocomplete('new-password')->dehydrated(fn ($state) => filled($state)),
                Select::make('status')->options(array_column(StaffStatus::cases(), 'value', 'value'))->default('ACTIVE')->required(),
                Select::make('roles')->multiple()->options(fn () => Role::where('guard_name', 'staff')->pluck('name', 'id'))->visible(fn () => auth('staff')->user()->can('manage staff roles')),
            ]);
    }
}
