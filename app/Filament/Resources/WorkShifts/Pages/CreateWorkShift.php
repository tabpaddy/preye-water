<?php

namespace App\Filament\Resources\WorkShifts\Pages;

use App\Filament\Resources\WorkShifts\WorkShiftResource;
use App\Services\WorkShiftService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateWorkShift extends CreateRecord
{
    protected static string $resource = WorkShiftResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(WorkShiftService::class)->create($data, auth('staff')->user());
    }
}
