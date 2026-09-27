<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use HasFactory, HasPublicUuid, SoftDeletes;

    protected $fillable = ['first_name', 'middle_name', 'last_name', 'phone', 'alternate_phone', 'email', 'gender', 'date_of_birth', 'profile_photo_path'];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date'];
    }

    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    public function getFullNameAttribute(): string
    {
        return implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name]));
    }
}
