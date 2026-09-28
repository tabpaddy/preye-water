<?php

namespace Database\Factories;

use App\Models\WorkShift;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkShiftFactory extends Factory
{
    protected $model = WorkShift::class;

    public function definition(): array
    {
        return ['name' => 'Morning', 'code' => fake()->unique()->bothify('SH-####??'), 'start_time' => '08:00', 'end_time' => '16:00', 'grace_period_minutes' => 10, 'break_minutes' => 30, 'is_overnight' => false, 'is_active' => true];
    }
}
