<?php

namespace App\Filament\Auth;

use App\Enums\StaffStatus;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('staff_number')->label('Staff number')->required()->autocomplete('username')->autofocus();
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return ['staff_number' => $data['staff_number'], 'password' => $data['password'], 'status' => StaffStatus::ACTIVE->value];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages(['data.staff_number' => __('auth.failed')]);
    }
}
