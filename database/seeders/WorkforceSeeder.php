<?php

namespace Database\Seeders;

use App\Models\NumberSequence;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class WorkforceSeeder extends Seeder
{
    public const PERMISSIONS = [
        'view departments', 'create departments', 'update departments', 'delete departments',
        'view job positions', 'create job positions', 'update job positions', 'delete job positions',
        'view staff employment details', 'update staff employment details', 'view staff salaries', 'update staff salaries',
        'view work shifts', 'create work shifts', 'update work shifts', 'delete work shifts',
        'view shift assignments', 'manage shift assignments',
        'view attendance', 'record attendance', 'adjust attendance', 'approve attendance adjustments',
        'view leave types', 'manage leave types',
        'view leave requests', 'create leave requests', 'approve leave requests', 'cancel leave requests',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'staff']);
        }
        NumberSequence::firstOrCreate(['key' => 'LEAVE'], ['prefix' => 'LEV', 'reset_frequency' => 'YEARLY', 'padding' => 6]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
