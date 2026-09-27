<?php

namespace Database\Factories;

use App\Enums\StaffStatus;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class StaffFactory extends Factory
{
    public function definition(): array
    {
        return ['person_id' => Person::factory(), 'staff_number' => 'STF-TEST-'.fake()->unique()->numerify('########'), 'password' => Hash::make('test-password-123'), 'status' => StaffStatus::ACTIVE, 'password_changed_at' => now()];
    }
}
