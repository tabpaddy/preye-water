<?php

namespace App\Enums;

enum SystemSettingType: string
{
    case STRING = 'STRING';
    case INTEGER = 'INTEGER';
    case DECIMAL = 'DECIMAL';
    case BOOLEAN = 'BOOLEAN';
    case JSON = 'JSON';
}
