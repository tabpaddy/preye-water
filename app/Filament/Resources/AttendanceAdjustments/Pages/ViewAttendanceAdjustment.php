<?php

namespace App\Filament\Resources\AttendanceAdjustments\Pages;

use App\Filament\Resources\AttendanceAdjustments\AttendanceAdjustmentResource;
use App\Filament\Support\WorkforceActions;
use Filament\Resources\Pages\ViewRecord;

class ViewAttendanceAdjustment extends ViewRecord
{
    protected static string $resource = AttendanceAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [...WorkforceActions::adjustments()];
    }
}
