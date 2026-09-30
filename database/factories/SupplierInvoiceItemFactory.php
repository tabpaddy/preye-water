<?php

namespace Database\Factories;

use App\Models\SupplierInvoice;
use App\Models\SupplierInvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierInvoiceItemFactory extends Factory
{
    protected $model = SupplierInvoiceItem::class;

    public function definition(): array
    {
        return ['supplier_invoice_id' => SupplierInvoice::factory(), 'description' => 'Materials', 'quantity' => '10.000', 'unit_cost' => '10.0000', 'line_total' => '100.00'];
    }
}
