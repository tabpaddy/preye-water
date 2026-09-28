<?php

namespace App\Filament\Resources\StaffLeaveRequests\Pages;

use App\Filament\Resources\StaffLeaveRequests\StaffLeaveRequestResource;
use App\Filament\Support\WorkforceActions;
use Filament\Resources\Pages\ViewRecord;

class ViewStaffLeaveRequest extends ViewRecord
{
    protected static string $resource = StaffLeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [...WorkforceActions::leave()];
    }
}
