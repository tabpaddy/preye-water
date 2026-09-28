<?php

namespace App\Enums;

enum AttendanceMethod: string
{
    case MANUAL = 'MANUAL';
    case STAFF_PORTAL = 'STAFF_PORTAL';
    case ADMIN = 'ADMIN';
    case BIOMETRIC = 'BIOMETRIC';
}
