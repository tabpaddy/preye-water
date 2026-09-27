<?php

namespace App\Enums;

enum ResetFrequency: string
{
    case NEVER = 'NEVER';
    case YEARLY = 'YEARLY';
    case MONTHLY = 'MONTHLY';
}
