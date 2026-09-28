<?php

namespace App\Filament\Resources\StaffShiftAssignments\Pages;

use App\Filament\Resources\StaffShiftAssignments\StaffShiftAssignmentResource;
use App\Services\StaffShiftService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStaffShiftAssignment extends CreateRecord
{
    protected static string $resource = StaffShiftAssignmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(StaffShiftService::class)->assign($data, auth('staff')->user());
    }
}
