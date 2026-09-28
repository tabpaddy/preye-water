<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\PayFrequency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffEmploymentDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        0 => 'staff_id',
        1 => 'department_id',
        2 => 'job_position_id',
        3 => 'employment_type',
        4 => 'employment_date',
        5 => 'confirmation_date',
        6 => 'termination_date',
        7 => 'basic_salary',
        8 => 'pay_frequency',
        9 => 'bank_name',
        10 => 'bank_account_name',
        11 => 'bank_account_number',
        12 => 'emergency_contact_name',
        13 => 'emergency_contact_phone',
        14 => 'emergency_contact_relationship',
    ];

    public const SENSITIVE = ['basic_salary', 'pay_frequency', 'bank_name', 'bank_account_name', 'bank_account_number'];

    protected $hidden = self::SENSITIVE;

    protected function casts(): array
    {
        return ['employment_type' => EmploymentType::class, 'pay_frequency' => PayFrequency::class, 'employment_date' => 'date', 'confirmation_date' => 'date', 'termination_date' => 'date', 'basic_salary' => 'decimal:2'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id')->withTrashed();
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function jobPosition(): BelongsTo
    {
        return $this->belongsTo(JobPosition::class);
    }
}
