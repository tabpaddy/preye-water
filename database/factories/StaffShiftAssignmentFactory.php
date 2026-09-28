<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\StaffShiftAssignment;
use App\Models\WorkShift;
use Illuminate\Database\Eloquent\Factories\Factory;

class StaffShiftAssignmentFactory extends Factory
{
    protected $model = StaffShiftAssignment::class;

    public function definition(): array
    {
        return ['staff_id' => Staff::factory(), 'work_shift_id' => WorkShift::factory(), 'effective_from' => '2026-01-01', 'assigned_by' => Staff::factory()];
    }
}
