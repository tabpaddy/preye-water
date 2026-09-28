<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Resources\Departments\DepartmentResource;
use App\Services\DepartmentService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditDepartment extends EditRecord
{
    protected static string $resource = DepartmentResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(DepartmentService::class)->update($record, $data, auth('staff')->user());
    }
}
