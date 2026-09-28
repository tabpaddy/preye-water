<?php

namespace App\Filament\Resources\StaffAttendances\Pages;

use App\Filament\Resources\StaffAttendances\StaffAttendanceResource;
use App\Services\AttendanceService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStaffAttendance extends CreateRecord
{
    protected static string $resource = StaffAttendanceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(AttendanceService::class)->create($data, auth('staff')->user());
    }
}
