<?php

namespace App\Models;

use App\Enums\SystemSettingType;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type', 'is_public', 'description'];

    protected function casts(): array
    {
        return ['type' => SystemSettingType::class, 'is_public' => 'boolean'];
    }
}
