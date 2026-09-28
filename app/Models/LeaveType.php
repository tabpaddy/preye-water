<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use \App\Models\Concerns\HasPublicUuid, HasFactory;

    protected $fillable = [
        0 => 'name',
        1 => 'code',
        2 => 'description',
        3 => 'is_paid',
        4 => 'requires_approval',
        5 => 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_paid' => 'boolean', 'requires_approval' => 'boolean', 'is_active' => 'boolean'];
    }

    public function requests(): HasMany
    {
        return $this->hasMany(StaffLeaveRequest::class);
    }
}
