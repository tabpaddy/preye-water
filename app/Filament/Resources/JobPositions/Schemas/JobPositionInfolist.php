<?php

namespace App\Filament\Resources\JobPositions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class JobPositionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([TextEntry::make('code'), TextEntry::make('name'), TextEntry::make('department.name'), TextEntry::make('is_active')]);
    }
}
