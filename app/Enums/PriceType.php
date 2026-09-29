<?php

namespace App\Enums;

enum PriceType: string
{
    case RETAIL = 'RETAIL';
    case WHOLESALE = 'WHOLESALE';
    case DISTRIBUTOR = 'DISTRIBUTOR';
}
