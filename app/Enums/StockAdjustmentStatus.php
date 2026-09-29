<?php

namespace App\Enums;

enum StockAdjustmentStatus: string
{
    case DRAFT = 'DRAFT';
    case PENDING_APPROVAL = 'PENDING_APPROVAL';
    case APPROVED = 'APPROVED';
    case POSTED = 'POSTED';
    case REJECTED = 'REJECTED';
    case CANCELLED = 'CANCELLED';
}
