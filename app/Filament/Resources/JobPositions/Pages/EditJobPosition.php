<?php

namespace App\Filament\Resources\JobPositions\Pages;

use App\Filament\Resources\JobPositions\JobPositionResource;
use App\Services\JobPositionService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditJobPosition extends EditRecord
{
    protected static string $resource = JobPositionResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(JobPositionService::class)->update($record, $data, auth('staff')->user());
    }
}
