<?php

namespace App\Models;

use App\Enums\AdjustmentType;
use App\Enums\AttendanceAdjustmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceAdjustment extends Model
{
    use \App\Models\Concerns\HasPublicUuid, HasFactory;

    protected $fillable = [
        0 => 'staff_attendance_id',
        1 => 'adjustment_type',
        2 => 'old_value',
        3 => 'new_value',
        4 => 'reason',
        5 => 'requested_by',
        6 => 'approved_by',
        7 => 'status',
        8 => 'approved_at',
    ];

    protected function casts(): array
    {
        return ['adjustment_type' => AdjustmentType::class, 'status' => AttendanceAdjustmentStatus::class, 'approved_at' => 'datetime'];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(StaffAttendance::class, 'staff_attendance_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'requested_by')->withTrashed();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by')->withTrashed();
    }
}
