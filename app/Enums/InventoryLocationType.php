<?php

namespace App\Enums;

enum InventoryLocationType: string
{
    case WAREHOUSE = 'WAREHOUSE';
    case PRODUCTION = 'PRODUCTION';
    case QC_HOLD = 'QC_HOLD';
    case TRUCK = 'TRUCK';
    case DAMAGED = 'DAMAGED';
    case QUARANTINE = 'QUARANTINE';
    case OTHER = 'OTHER';
}
