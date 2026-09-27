<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\NumberSequence;
use Illuminate\Database\Seeder;

class BusinessSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::firstOrCreate(['id' => 1], ['name' => 'Preye Water', 'country' => 'Nigeria']);
        $business->setting()->firstOrCreate([], ['currency' => 'NGN', 'timezone' => 'Africa/Lagos']);
        NumberSequence::firstOrCreate(['key' => 'STAFF'], ['prefix' => 'STF', 'reset_frequency' => 'YEARLY', 'padding' => 6]);
    }
}
