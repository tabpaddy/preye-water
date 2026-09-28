<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\StaffEmploymentDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

class StaffEmploymentDetailFactory extends Factory
{
    protected $model = StaffEmploymentDetail::class;

    public function definition(): array
    {
        return ['staff_id' => Staff::factory(), 'employment_type' => 'FULL_TIME', 'employment_date' => '2026-01-01'];
    }
}
