<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case DRAFT = 'DRAFT';
    case PENDING_APPROVAL = 'PENDING_APPROVAL';
    case APPROVED = 'APPROVED';
    case PARTIALLY_RECEIVED = 'PARTIALLY_RECEIVED';
    case RECEIVED = 'RECEIVED';
    case CANCELLED = 'CANCELLED';
    case CLOSED = 'CLOSED';
}
