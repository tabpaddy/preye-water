<?php

namespace App\Models;

use App\Enums\AttendanceMethod;
use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffAttendance extends Model
{
    use \App\Models\Concerns\HasPublicUuid, HasFactory;

    protected $fillable = [
        0 => 'staff_id',
        1 => 'work_shift_id',
        2 => 'attendance_date',
        3 => 'status',
        4 => 'clock_in_at',
        5 => 'clock_out_at',
        6 => 'late_minutes',
        7 => 'early_departure_minutes',
        8 => 'worked_minutes',
        9 => 'overtime_minutes',
        10 => 'clock_in_method',
        11 => 'clock_out_method',
        12 => 'notes',
        13 => 'shift_snapshot',
    ];

    protected $table = 'staff_attendance';

    protected function casts(): array
    {
        return ['attendance_date' => 'date', 'status' => AttendanceStatus::class, 'clock_in_at' => 'immutable_datetime', 'clock_out_at' => 'immutable_datetime', 'clock_in_method' => AttendanceMethod::class, 'clock_out_method' => AttendanceMethod::class, 'shift_snapshot' => 'array', 'late_minutes' => 'integer', 'early_departure_minutes' => 'integer', 'worked_minutes' => 'integer', 'overtime_minutes' => 'integer'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id')->withTrashed();
    }

    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(AttendanceAdjustment::class, 'staff_attendance_id');
    }
}
