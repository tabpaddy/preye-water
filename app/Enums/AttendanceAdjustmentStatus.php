<?php

namespace App\Enums;

enum AttendanceAdjustmentStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
}
