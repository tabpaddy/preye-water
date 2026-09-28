<?php

namespace App\Filament\Resources\LeaveTypes\Pages;

use App\Filament\Resources\LeaveTypes\LeaveTypeResource;
use App\Services\LeaveTypeService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateLeaveType extends CreateRecord
{
    protected static string $resource = LeaveTypeResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(LeaveTypeService::class)->create($data, auth('staff')->user());
    }
}
