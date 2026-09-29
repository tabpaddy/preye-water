<?php

namespace App\Filament\Resources\InventoryItems\Pages;

use App\Filament\Resources\InventoryItems\InventoryItemResource;
use App\Services\InventoryItemService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditInventoryItem extends EditRecord
{
    protected static string $resource = InventoryItemResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(InventoryItemService::class)->update($record, $data, auth('staff')->user());
    }
}
