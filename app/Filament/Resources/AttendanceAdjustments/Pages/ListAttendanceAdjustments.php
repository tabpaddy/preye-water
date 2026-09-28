<?php

namespace App\Filament\Resources\AttendanceAdjustments\Pages;

use App\Filament\Resources\AttendanceAdjustments\AttendanceAdjustmentResource;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceAdjustments extends ListRecords
{
    protected static string $resource = AttendanceAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
