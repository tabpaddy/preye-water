<?php

namespace App\Filament\Resources\LeaveTypes\Pages;

use App\Filament\Resources\LeaveTypes\LeaveTypeResource;
use App\Services\LeaveTypeService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditLeaveType extends EditRecord
{
    protected static string $resource = LeaveTypeResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(LeaveTypeService::class)->update($record, $data, auth('staff')->user());
    }
}
