<?php

namespace App\Filament\Widgets;

use App\Enums\StaffStatus;
use App\Models\Business;
use App\Models\Staff;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FoundationOverview extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth('staff')->user()?->can('view staff') ?? false;
    }

    protected function getStats(): array
    {
        return [Stat::make('Active staff', Staff::where('status', StaffStatus::ACTIVE)->count()), Stat::make('Inactive / suspended staff', Staff::whereIn('status', [StaffStatus::INACTIVE, StaffStatus::SUSPENDED])->count()), Stat::make('Business', Business::value('name') ?? 'Preye Water')];
    }
}
