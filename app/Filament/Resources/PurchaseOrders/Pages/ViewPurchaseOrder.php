<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Filament\Support\ProcurementActions;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), ...ProcurementActions::purchaseOrder()];
    }
}
