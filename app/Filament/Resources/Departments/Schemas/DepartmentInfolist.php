<?php

namespace App\Filament\Resources\Departments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DepartmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([TextEntry::make('code'), TextEntry::make('name'), TextEntry::make('manager.staff_number'), TextEntry::make('is_active')]);
    }
}
