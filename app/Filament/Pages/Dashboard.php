<?php

namespace App\Filament\Pages;

class Dashboard extends \Filament\Pages\Dashboard
{
    public static function canAccess(): bool
    {
        return auth('staff')->user()?->can('view dashboard') ?? false;
    }
}
