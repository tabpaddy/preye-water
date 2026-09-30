<?php

namespace App\Enums;

enum SupplierType: string
{
    case RAW_MATERIAL = 'RAW_MATERIAL';
    case PACKAGING = 'PACKAGING';
    case CHEMICAL = 'CHEMICAL';
    case FUEL = 'FUEL';
    case MAINTENANCE = 'MAINTENANCE';
    case GENERAL = 'GENERAL';
}
