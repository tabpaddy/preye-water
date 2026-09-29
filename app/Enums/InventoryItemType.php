<?php

namespace App\Enums;

enum InventoryItemType: string
{
    case FINISHED_GOOD = 'FINISHED_GOOD';
    case RAW_MATERIAL = 'RAW_MATERIAL';
    case PACKAGING = 'PACKAGING';
    case CONSUMABLE = 'CONSUMABLE';
    case SPARE_PART = 'SPARE_PART';
}
