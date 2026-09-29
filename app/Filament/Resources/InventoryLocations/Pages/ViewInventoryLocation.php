<?php

namespace App\Filament\Resources\InventoryLocations\Pages;

use App\Filament\Resources\InventoryLocations\InventoryLocationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInventoryLocation extends ViewRecord
{
    protected static string $resource = InventoryLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
