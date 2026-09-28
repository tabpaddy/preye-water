<?php

namespace App\Filament\Resources\StaffShiftAssignments\Pages;

use App\Filament\Resources\StaffShiftAssignments\StaffShiftAssignmentResource;
use App\Services\StaffShiftService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditStaffShiftAssignment extends EditRecord
{
    protected static string $resource = StaffShiftAssignmentResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(StaffShiftService::class)->update($record, $data, auth('staff')->user());
    }
}
