<?php

namespace Database\Seeders;

use App\Models\NumberSequence;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class ProcurementSeeder extends Seeder
{
    public const PERMISSIONS = [
        0 => 'view suppliers',
        1 => 'create suppliers',
        2 => 'update suppliers',
        3 => 'archive suppliers',
        4 => 'view purchase orders',
        5 => 'create purchase orders',
        6 => 'update purchase orders',
        7 => 'submit purchase orders',
        8 => 'approve purchase orders',
        9 => 'cancel purchase orders',
        10 => 'close purchase orders',
        11 => 'view goods receipts',
        12 => 'create goods receipts',
        13 => 'update goods receipts',
        14 => 'inspect goods receipts',
        15 => 'post goods receipts',
        16 => 'cancel goods receipts',
        17 => 'view supplier invoices',
        18 => 'create supplier invoices',
        19 => 'update supplier invoices',
        20 => 'view supplier payments',
        21 => 'create supplier payments',
        22 => 'approve supplier payments',
        23 => 'allocate supplier payments',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'staff']);
        }
        foreach (['SUPPLIER' => 'SUP', 'PURCHASE_ORDER' => 'PO', 'GOODS_RECEIPT' => 'GRN', 'SUPPLIER_INVOICE' => 'SIN', 'SUPPLIER_PAYMENT' => 'SPY'] as $key => $prefix) {
            NumberSequence::firstOrCreate(['key' => $key], ['prefix' => $prefix, 'reset_frequency' => 'YEARLY', 'padding' => 6]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
