<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['view dashboard', 'view staff', 'create staff', 'update staff', 'delete staff', 'manage staff roles', 'manage staff permissions', 'view business settings', 'update business settings', 'view system settings', 'update system settings', 'view activity logs'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'staff']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
