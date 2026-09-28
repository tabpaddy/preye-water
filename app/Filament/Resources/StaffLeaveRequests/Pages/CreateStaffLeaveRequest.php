<?php

namespace App\Filament\Resources\StaffLeaveRequests\Pages;

use App\Filament\Resources\StaffLeaveRequests\StaffLeaveRequestResource;
use App\Services\LeaveService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStaffLeaveRequest extends CreateRecord
{
    protected static string $resource = StaffLeaveRequestResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(LeaveService::class)->submit($data, auth('staff')->user());
    }
}
