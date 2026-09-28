<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPosition extends Model
{
    use \App\Models\Concerns\HasPublicUuid, HasFactory;

    protected $fillable = [
        0 => 'department_id',
        1 => 'code',
        2 => 'name',
        3 => 'description',
        4 => 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function staffEmploymentDetails(): HasMany
    {
        return $this->hasMany(StaffEmploymentDetail::class);
    }
}
