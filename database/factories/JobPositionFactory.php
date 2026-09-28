<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\JobPosition;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobPositionFactory extends Factory
{
    protected $model = JobPosition::class;

    public function definition(): array
    {
        return ['department_id' => Department::factory(), 'code' => fake()->unique()->bothify('JOB-####??'), 'name' => 'Operator', 'is_active' => true];
    }
}
