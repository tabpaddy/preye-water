<?php

namespace App\Filament\Resources\InventoryLocations\Pages;

use App\Filament\Resources\InventoryLocations\InventoryLocationResource;
use App\Services\InventoryLocationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateInventoryLocation extends CreateRecord
{
    protected static string $resource = InventoryLocationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(InventoryLocationService::class)->create($data, auth('staff')->user());
    }
}
