<?php

namespace App\Filament\Auth;

use App\Enums\StaffStatus;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;

class RequestPasswordReset extends \Filament\Auth\Pages\PasswordReset\RequestPasswordReset
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('staff_number')->label('Staff number')->helperText('A reset link will be sent to the email address on your staff profile.')->required()->autocomplete('username');
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return ['staff_number' => $data['staff_number'], 'status' => StaffStatus::ACTIVE->value, fn ($query) => $query->whereHas('person', fn ($person) => $person->whereNotNull('email'))];
    }

    protected function getFailureNotification(string $status): ?Notification
    {
        return $this->getSentNotification($status);
    }

    protected function getSentNotification(string $status): ?Notification
    {
        return Notification::make()->title('If this account can receive password resets, a link has been sent.')->success();
    }
}
