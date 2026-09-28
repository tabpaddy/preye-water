<?php

namespace App\Enums;

enum AdjustmentType: string
{
    case CLOCK_IN = 'CLOCK_IN';
    case CLOCK_OUT = 'CLOCK_OUT';
    case STATUS = 'STATUS';
    case SHIFT = 'SHIFT';
}
