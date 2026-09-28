<?php

namespace App\Filament\Resources\StaffShiftAssignments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class StaffShiftAssignmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([TextEntry::make('staff.staff_number'), TextEntry::make('staff.person.full_name'), TextEntry::make('workShift.name'), TextEntry::make('effective_from'), TextEntry::make('effective_until')]);
    }
}
