<?php

namespace App\Filament\Auth;

use App\Enums\StaffStatus;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;

class ResetPassword extends \Filament\Auth\Pages\PasswordReset\ResetPassword
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')->label('Staff number')->disabled();
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()->minLength(12);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return ['staff_number' => $data['email'], 'status' => StaffStatus::ACTIVE->value, 'password' => $data['password'], 'token' => $data['token']];
    }
}
