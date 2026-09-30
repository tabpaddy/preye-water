<?php

namespace App\Filament\Resources\GoodsReceipts\Pages;

use App\Filament\Resources\GoodsReceipts\GoodsReceiptResource;
use App\Services\GoodsReceiptService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditGoodsReceipt extends EditRecord
{
    protected static string $resource = GoodsReceiptResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['items'] = $this->record->items->map(fn ($line) => $line->toArray() + [
            'ordered' => $line->purchaseOrderItem->quantity_ordered,
            'previously_received' => $line->purchaseOrderItem->quantity_received,
            'remaining' => $line->purchaseOrderItem->remaining_quantity,
        ])->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(GoodsReceiptService::class)->update($record, $data, auth('staff')->user());
    }
}
