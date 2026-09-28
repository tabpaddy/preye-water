<?php

namespace App\Filament\Resources\LeaveTypes\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LeaveTypeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([TextEntry::make('code'), TextEntry::make('name'), TextEntry::make('is_paid'), TextEntry::make('requires_approval'), TextEntry::make('is_active')]);
    }
}
