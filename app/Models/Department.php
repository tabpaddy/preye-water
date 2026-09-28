<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use \App\Models\Concerns\HasPublicUuid, HasFactory;

    protected $fillable = [
        0 => 'code',
        1 => 'name',
        2 => 'description',
        3 => 'manager_id',
        4 => 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'manager_id')->withTrashed();
    }

    public function jobPositions(): HasMany
    {
        return $this->hasMany(JobPosition::class);
    }

    public function staffEmploymentDetails(): HasMany
    {
        return $this->hasMany(StaffEmploymentDetail::class);
    }
}
