<?php

namespace App\Enums;

enum PayFrequency: string
{
    case DAILY = 'DAILY';
    case WEEKLY = 'WEEKLY';
    case BIWEEKLY = 'BIWEEKLY';
    case MONTHLY = 'MONTHLY';
}
