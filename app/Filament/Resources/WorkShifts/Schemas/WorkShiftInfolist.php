<?php

namespace App\Filament\Resources\WorkShifts\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class WorkShiftInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([TextEntry::make('code'), TextEntry::make('name'), TextEntry::make('start_time'), TextEntry::make('end_time'), TextEntry::make('is_overnight'), TextEntry::make('break_minutes'), TextEntry::make('is_active')]);
    }
}
