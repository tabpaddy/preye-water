<?php

namespace App\Filament\Resources\StaffShiftAssignments\Pages;

use App\Filament\Resources\StaffShiftAssignments\StaffShiftAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStaffShiftAssignments extends ListRecords
{
    protected static string $resource = StaffShiftAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
