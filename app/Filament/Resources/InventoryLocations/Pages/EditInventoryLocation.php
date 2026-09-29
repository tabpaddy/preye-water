<?php

namespace App\Filament\Resources\InventoryLocations\Pages;

use App\Filament\Resources\InventoryLocations\InventoryLocationResource;
use App\Services\InventoryLocationService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditInventoryLocation extends EditRecord
{
    protected static string $resource = InventoryLocationResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(InventoryLocationService::class)->update($record, $data, auth('staff')->user());
    }
}
