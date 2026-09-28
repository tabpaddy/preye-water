<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkShift extends Model
{
    use \App\Models\Concerns\HasPublicUuid, HasFactory;

    protected $fillable = [
        0 => 'name',
        1 => 'code',
        2 => 'start_time',
        3 => 'end_time',
        4 => 'grace_period_minutes',
        5 => 'break_minutes',
        6 => 'is_overnight',
        7 => 'is_active',
    ];

    protected function casts(): array
    {
        return ['grace_period_minutes' => 'integer', 'break_minutes' => 'integer', 'is_overnight' => 'boolean', 'is_active' => 'boolean'];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(StaffShiftAssignment::class);
    }
}
