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
        $manager->syncPermissions(['view dashboard', 'view staff', 'view business settings']);
    }
}
