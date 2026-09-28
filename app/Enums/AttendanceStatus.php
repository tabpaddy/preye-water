<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'PRESENT';
    case LATE = 'LATE';
    case ABSENT = 'ABSENT';
    case ON_LEAVE = 'ON_LEAVE';
    case OFF_DAY = 'OFF_DAY';
    case HALF_DAY = 'HALF_DAY';
}
