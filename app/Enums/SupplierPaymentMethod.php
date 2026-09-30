<?php

namespace App\Enums;

enum SupplierPaymentMethod: string
{
    case CASH = 'CASH';
    case BANK_TRANSFER = 'BANK_TRANSFER';
    case POS = 'POS';
    case CHEQUE = 'CHEQUE';
    case OTHER = 'OTHER';
}
