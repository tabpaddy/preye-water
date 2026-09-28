<?php

namespace App\Filament\Resources\Staff\Schemas;

use App\Services\BusinessSettingService;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class StaffInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([...StaffEmploymentInfolist::components(),
                TextEntry::make('staff_number'),
                TextEntry::make('person.full_name')->label('Name'),
                TextEntry::make('person.email'),
                TextEntry::make('person.phone'),
                TextEntry::make('status')->badge(),
                TextEntry::make('roles.name')->badge(),
                TextEntry::make('last_login_at')->dateTime(fn () => app(BusinessSettingService::class)->dateTimeFormat(), fn () => app(BusinessSettingService::class)->get()->timezone),
            ]);
    }
}
