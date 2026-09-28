<?php

namespace App\Models;

use App\Enums\LeaveRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffLeaveRequest extends Model
{
    use \App\Models\Concerns\HasPublicUuid, HasFactory;

    protected $fillable = [
        0 => 'request_number',
        1 => 'staff_id',
        2 => 'leave_type_id',
        3 => 'start_date',
        4 => 'end_date',
        5 => 'total_days',
        6 => 'reason',
        7 => 'status',
        8 => 'approved_by',
        9 => 'approved_at',
        10 => 'rejection_reason',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'total_days' => 'decimal:2', 'status' => LeaveRequestStatus::class, 'approved_at' => 'datetime'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id')->withTrashed();
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by')->withTrashed();
    }
}
