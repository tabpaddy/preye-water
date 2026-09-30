<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Services\ProcurementService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['items'] = $this->record->items->toArray();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(ProcurementService::class)->update($record, $data, auth('staff')->user());
    }
}
