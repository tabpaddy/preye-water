<?php

namespace App\Enums;

enum StockAdjustmentReason: string
{
    case PHYSICAL_COUNT = 'PHYSICAL_COUNT';
    case DAMAGE = 'DAMAGE';
    case LOSS = 'LOSS';
    case FOUND_STOCK = 'FOUND_STOCK';
    case DATA_CORRECTION = 'DATA_CORRECTION';
    case OTHER = 'OTHER';
}
