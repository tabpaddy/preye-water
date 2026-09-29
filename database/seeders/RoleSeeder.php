<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'staff']);
        $manager = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'staff']);
        $manager->givePermissionTo(['view dashboard', 'view staff', 'view business settings', 'view departments', 'view job positions', 'view staff employment details', 'view work shifts', 'view shift assignments', 'view attendance', 'view leave types', 'view leave requests', 'view inventory items', 'view products', 'view product prices', 'view inventory locations', 'view inventory stocks', 'view stock movements', 'view stock adjustments']);
    }
}
