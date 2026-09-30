<?php

namespace App\Enums;

enum GoodsReceiptStatus: string
{
    case DRAFT = 'DRAFT';
    case INSPECTED = 'INSPECTED';
    case POSTED = 'POSTED';
    case REJECTED = 'REJECTED';
    case CANCELLED = 'CANCELLED';
}
