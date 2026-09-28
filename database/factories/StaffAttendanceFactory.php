<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\StaffAttendance;
use Illuminate\Database\Eloquent\Factories\Factory;

class StaffAttendanceFactory extends Factory
{
    protected $model = StaffAttendance::class;

    public function definition(): array
    {
        return ['staff_id' => Staff::factory(), 'attendance_date' => '2026-09-28', 'status' => 'ABSENT'];
    }
}
