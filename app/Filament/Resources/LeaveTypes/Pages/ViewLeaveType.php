<?php

namespace App\Filament\Resources\LeaveTypes\Pages;

use App\Filament\Resources\LeaveTypes\LeaveTypeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLeaveType extends ViewRecord
{
    protected static string $resource = LeaveTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), ...[]];
    }
}
