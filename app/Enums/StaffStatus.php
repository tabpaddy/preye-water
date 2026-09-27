<?php

namespace App\Enums;

enum StaffStatus: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case SUSPENDED = 'SUSPENDED';
    case TERMINATED = 'TERMINATED';
}
