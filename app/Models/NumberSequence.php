<?php

namespace App\Models;

use App\Enums\ResetFrequency;
use Illuminate\Database\Eloquent\Model;

class NumberSequence extends Model
{
    protected $fillable = ['key', 'prefix', 'current_number', 'padding', 'reset_frequency', 'last_reset_at'];

    protected function casts(): array
    {
        return ['reset_frequency' => ResetFrequency::class, 'last_reset_at' => 'datetime', 'current_number' => 'integer', 'padding' => 'integer'];
    }
}
