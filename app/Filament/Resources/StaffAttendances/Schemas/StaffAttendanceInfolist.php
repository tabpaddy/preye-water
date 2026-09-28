<?php

namespace App\Filament\Resources\StaffAttendances\Schemas;

use App\Services\BusinessSettingService;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class StaffAttendanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([TextEntry::make('attendance_date'), TextEntry::make('staff.staff_number'), TextEntry::make('staff.person.full_name'), TextEntry::make('staff.employmentDetail.department.name'), TextEntry::make('workShift.name'), TextEntry::make('clock_in_at')->dateTime(fn () => app(BusinessSettingService::class)->dateTimeFormat(), fn () => app(BusinessSettingService::class)->get()->timezone), TextEntry::make('clock_out_at')->dateTime(fn () => app(BusinessSettingService::class)->dateTimeFormat(), fn () => app(BusinessSettingService::class)->get()->timezone), TextEntry::make('status')->badge(), TextEntry::make('late_minutes'), TextEntry::make('early_departure_minutes'), TextEntry::make('worked_minutes'), TextEntry::make('overtime_minutes')]);
    }
}
