<?php

namespace Database\Factories;

use App\Models\LeaveType;
use App\Models\Staff;
use App\Models\StaffLeaveRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class StaffLeaveRequestFactory extends Factory
{
    protected $model = StaffLeaveRequest::class;

    public function definition(): array
    {
        return ['request_number' => fake()->unique()->bothify('LEV-TEST-######??'), 'staff_id' => Staff::factory(), 'leave_type_id' => LeaveType::factory(), 'start_date' => '2026-10-01', 'end_date' => '2026-10-02', 'total_days' => '2.00', 'status' => 'PENDING'];
    }
}
