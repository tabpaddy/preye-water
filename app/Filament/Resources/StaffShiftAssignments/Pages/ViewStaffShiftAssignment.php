<?php

namespace App\Filament\Resources\StaffShiftAssignments\Pages;

use App\Filament\Resources\StaffShiftAssignments\StaffShiftAssignmentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStaffShiftAssignment extends ViewRecord
{
    protected static string $resource = StaffShiftAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), ...[]];
    }
}
