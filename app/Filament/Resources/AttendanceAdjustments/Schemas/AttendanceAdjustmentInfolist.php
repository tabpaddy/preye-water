<?php

namespace App\Filament\Resources\AttendanceAdjustments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AttendanceAdjustmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([TextEntry::make('attendance.attendance_date'), TextEntry::make('attendance.staff.staff_number'), TextEntry::make('adjustment_type')->badge(), TextEntry::make('old_value'), TextEntry::make('new_value'), TextEntry::make('reason'), TextEntry::make('status')->badge(), TextEntry::make('requester.staff_number'), TextEntry::make('approver.staff_number')]);
    }
}
