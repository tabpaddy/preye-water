<?php

namespace Database\Seeders;

use App\Models\NumberSequence;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class InventorySeeder extends Seeder
{
    public const PERMISSIONS = [
        0 => 'view inventory items',
        1 => 'create inventory items',
        2 => 'update inventory items',
        3 => 'archive inventory items',
        4 => 'view products',
        5 => 'create products',
        6 => 'update products',
        7 => 'archive products',
        8 => 'view product prices',
        9 => 'manage product prices',
        10 => 'view inventory locations',
        11 => 'create inventory locations',
        12 => 'update inventory locations',
        13 => 'view inventory stocks',
        14 => 'view stock movements',
        15 => 'view stock adjustments',
        16 => 'create stock adjustments',
        17 => 'update stock adjustments',
        18 => 'submit stock adjustments',
        19 => 'approve stock adjustments',
        20 => 'post stock adjustments',
        21 => 'cancel stock adjustments',
        22 => 'transfer inventory',
        23 => 'reserve inventory',
        24 => 'operate inventory',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'staff']);
        }
        NumberSequence::firstOrCreate(['key' => 'STOCK_ADJUSTMENT'], ['prefix' => 'ADJ', 'reset_frequency' => 'YEARLY', 'padding' => 6]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
