<?php

namespace App\Filament\Resources\ActivityLogs\Schemas;

use App\Services\BusinessSettingService;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ActivityLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('event'),
                TextEntry::make('staff.staff_number')->label('Actor'),
                TextEntry::make('description'),
                TextEntry::make('subject_type'),
                TextEntry::make('subject_id'),
                TextEntry::make('created_at')->dateTime(fn () => app(BusinessSettingService::class)->dateTimeFormat(), fn () => app(BusinessSettingService::class)->get()->timezone),
                TextEntry::make('old_values')->getStateUsing(fn ($record) => json_encode($record->old_values, JSON_PRETTY_PRINT)),
                TextEntry::make('new_values')->getStateUsing(fn ($record) => json_encode($record->new_values, JSON_PRETTY_PRINT)),
                TextEntry::make('metadata')->getStateUsing(fn ($record) => json_encode($record->metadata, JSON_PRETTY_PRINT)),
            ]);
    }
}
