<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffShiftAssignment extends Model
{
    use \App\Models\Concerns\HasPublicUuid, HasFactory;

    protected $fillable = [
        0 => 'staff_id',
        1 => 'work_shift_id',
        2 => 'effective_from',
        3 => 'effective_until',
        4 => 'assigned_by',
        5 => 'notes',
    ];

    protected function casts(): array
    {
        return ['effective_from' => 'date', 'effective_until' => 'date'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id')->withTrashed();
    }

    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_by')->withTrashed();
    }
}
