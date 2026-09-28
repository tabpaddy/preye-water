<?php

namespace App\Filament\Resources\StaffLeaveRequests\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class StaffLeaveRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([TextEntry::make('request_number'), TextEntry::make('staff.staff_number'), TextEntry::make('staff.person.full_name'), TextEntry::make('leaveType.name'), TextEntry::make('start_date'), TextEntry::make('end_date'), TextEntry::make('total_days'), TextEntry::make('reason'), TextEntry::make('status')->badge(), TextEntry::make('approver.staff_number')]);
    }
}
