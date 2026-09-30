<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use App\Models\SupplierPaymentAllocation;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierPaymentAllocationFactory extends Factory
{
    protected $model = SupplierPaymentAllocation::class;

    public function definition(): array
    {
        return ['supplier_payment_id' => SupplierPayment::factory()->state(['status' => 'COMPLETED', 'paid_at' => now(), 'approved_by' => Staff::factory()]), 'supplier_invoice_id' => fn (array $a) => SupplierInvoice::factory()->create(['supplier_id' => SupplierPayment::findOrFail($a['supplier_payment_id'])->supplier_id])->id, 'amount' => '10.00'];
    }
}
