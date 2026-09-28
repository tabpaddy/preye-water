<?php

namespace Database\Factories;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    public function definition(): array
    {
        return ['name' => 'Annual', 'code' => fake()->unique()->bothify('LV-####??'), 'is_paid' => true, 'requires_approval' => true, 'is_active' => true];
    }
}
