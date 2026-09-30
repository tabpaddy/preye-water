<?php

namespace App\Filament\Resources\Suppliers\Pages;

use App\Filament\Resources\Suppliers\SupplierResource;
use App\Services\SupplierService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSupplier extends EditRecord
{
    protected static string $resource = SupplierResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(SupplierService::class)->update($record, $data, auth('staff')->user());
    }
}
