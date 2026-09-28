<?php

namespace App\Filament\Resources\WorkShifts\Pages;

use App\Filament\Resources\WorkShifts\WorkShiftResource;
use App\Services\WorkShiftService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditWorkShift extends EditRecord
{
    protected static string $resource = WorkShiftResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(WorkShiftService::class)->update($record, $data, auth('staff')->user());
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['start_time'] = substr($data['start_time'], 0, 5);
        $data['end_time'] = substr($data['end_time'], 0, 5);

        return $data;
    }
}
