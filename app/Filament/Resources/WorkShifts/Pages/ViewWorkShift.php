<?php

namespace App\Filament\Resources\WorkShifts\Pages;

use App\Filament\Resources\WorkShifts\WorkShiftResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWorkShift extends ViewRecord
{
    protected static string $resource = WorkShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), ...[]];
    }
}
