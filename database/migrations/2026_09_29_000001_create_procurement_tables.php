<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('supplier_number')->unique();
            $t->string('name');
            foreach (['supplier_type', 'contact_person', 'phone', 'alternate_phone', 'email', 'tax_identification_number'] as $c) {
                $t->string($c)->nullable();
            }
            $t->unsignedInteger('payment_terms_days')->nullable();
            $t->string('status')->default('ACTIVE');
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('supplier_addresses', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $t->string('label')->nullable();
            $t->string('address_line_1');
            $t->string('address_line_2')->nullable();
            $t->string('landmark')->nullable();
            $t->string('city');
            $t->string('lga')->nullable();
            $t->string('state');
            $t->string('country')->default('Nigeria');
            $t->string('postal_code')->nullable();
            $t->boolean('is_default')->default(false);
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('default_supplier_id')->nullable()->virtualAs('CASE WHEN is_default = 1 THEN supplier_id ELSE NULL END')->unique();
            $t->timestamps();
        });
        Schema::create('purchase_orders', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('purchase_order_number')->unique();
            $t->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $t->string('status')->default('DRAFT');
            $t->date('order_date');
            $t->date('expected_delivery_date')->nullable();
            foreach (['subtotal', 'discount_amount', 'tax_amount', 'other_cost', 'total_amount'] as $c) {
                $t->decimal($c, 15, 2)->default(0);
            }
            $t->string('supplier_reference')->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->constrained('staff')->restrictOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('staff')->restrictOnDelete();
            $t->dateTime('approved_at')->nullable();
            $t->timestamps();
            $t->index(['supplier_id', 'order_date']);
            $t->index(['status', 'order_date']);
        });
        Schema::create('purchase_order_items', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $t->foreignId('inventory_item_id')->constrained()->restrictOnDelete();
            $t->string('item_name');
            $t->string('sku');
            $t->decimal('quantity_ordered', 15, 3);
            $t->decimal('quantity_received', 15, 3)->default(0);
            $t->decimal('unit_cost', 15, 4);
            $t->decimal('discount_amount', 15, 2)->default(0);
            $t->decimal('tax_amount', 15, 2)->default(0);
            $t->decimal('line_total', 15, 2);
            $t->timestamps();
            $t->unique(['purchase_order_id', 'inventory_item_id'], 'po_item_unique');
        });
        Schema::create('goods_receipts', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('goods_receipt_number')->unique();
            $t->foreignId('purchase_order_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $t->foreignId('inventory_location_id')->constrained()->restrictOnDelete();
            $t->string('supplier_delivery_note')->nullable();
            $t->string('status')->default('DRAFT');
            $t->dateTime('received_at');
            foreach (['received_by', 'inspected_by', 'posted_by'] as $c) {
                $column = $t->foreignId($c);
                if ($c !== 'received_by') {
                    $column->nullable();
                }
                $column->constrained('staff')->restrictOnDelete();
            }
            $t->dateTime('posted_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['supplier_id', 'received_at']);
            $t->index(['status', 'received_at']);
        });
        Schema::create('goods_receipt_items', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('goods_receipt_id')->constrained()->restrictOnDelete();
            $t->foreignId('purchase_order_item_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('inventory_item_id')->constrained()->restrictOnDelete();
            foreach (['quantity_received', 'quantity_accepted', 'quantity_rejected'] as $c) {
                $t->decimal($c, 15, 3)->default(0);
            }
            $t->decimal('unit_cost', 15, 4);
            $t->text('rejection_reason')->nullable();
            $t->timestamps();
            $t->unique(['goods_receipt_id', 'inventory_item_id'], 'grn_item_unique');
        });
        Schema::create('supplier_invoices', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $t->foreignId('purchase_order_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('invoice_number');
            $t->string('internal_reference')->unique();
            $t->date('invoice_date');
            $t->date('due_date')->nullable();
            foreach (['subtotal', 'discount_amount', 'tax_amount', 'other_amount', 'total_amount', 'amount_paid', 'amount_due'] as $c) {
                $t->decimal($c, 15, 2)->default(0);
            }
            $t->string('payment_status')->default('UNPAID');
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->constrained('staff')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['supplier_id', 'invoice_number']);
            $t->index(['payment_status', 'due_date']);
            $t->index('invoice_date');
        });
        Schema::create('supplier_invoice_items', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('supplier_invoice_id')->constrained()->restrictOnDelete();
            $t->foreignId('purchase_order_item_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('inventory_item_id')->nullable()->constrained()->restrictOnDelete();
            $t->text('description');
            $t->decimal('quantity', 15, 3);
            $t->decimal('unit_cost', 15, 4);
            $t->decimal('discount_amount', 15, 2)->default(0);
            $t->decimal('tax_amount', 15, 2)->default(0);
            $t->decimal('line_total', 15, 2);
            $t->timestamps();
        });
        Schema::create('supplier_payments', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('payment_number')->unique();
            $t->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $t->string('payment_method');
            $t->decimal('amount', 15, 2);
            $t->string('currency', 3)->default('NGN');
            $t->string('reference')->nullable();
            $t->string('status')->default('PENDING');
            $t->dateTime('paid_at')->nullable();
            $t->foreignId('recorded_by')->constrained('staff')->restrictOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('staff')->restrictOnDelete();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['supplier_id', 'paid_at']);
            $t->index(['status', 'paid_at']);
        });
        Schema::create('supplier_payment_allocations', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('supplier_payment_id')->constrained()->restrictOnDelete();
            $t->foreignId('supplier_invoice_id')->constrained()->restrictOnDelete();
            $t->decimal('amount', 15, 2);
            $t->timestamps();
            $t->unique(['supplier_payment_id', 'supplier_invoice_id'], 'supplier_allocation_unique');
        });
        $this->check('purchase_order_items', 'po_quantities_valid', 'quantity_ordered > 0 AND quantity_received >= 0 AND quantity_received <= quantity_ordered AND unit_cost >= 0');
        $this->check('goods_receipt_items', 'grn_quantities_valid', 'quantity_received > 0 AND quantity_accepted >= 0 AND quantity_rejected >= 0 AND ROUND(quantity_accepted + quantity_rejected, 3) = quantity_received AND unit_cost >= 0');
        $this->check('supplier_invoices', 'supplier_invoice_balances_valid', 'total_amount > 0 AND amount_paid >= 0 AND amount_due >= 0 AND ROUND(amount_paid + amount_due, 2) = total_amount');
        $this->check('supplier_payments', 'supplier_payment_positive', 'amount > 0');
        $this->check('supplier_payment_allocations', 'supplier_allocation_positive', 'amount > 0');
    }

    private function check(string $table, string $name, string $expression): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $columns = Schema::getColumnListing($table);
            $expression = preg_replace_callback('/\b[a-z_]+\b/', fn($m) => in_array($m[0], $columns, true) ? 'NEW.' . $m[0] : $m[0], $expression);
            foreach (['INSERT', 'UPDATE'] as $operation) {
                DB::unprepared("CREATE TRIGGER {$name}_" . strtolower($operation) . " BEFORE $operation ON $table WHEN NOT ($expression) BEGIN SELECT RAISE(ABORT, '$name'); END");
            }
        } else {
            DB::statement("ALTER TABLE $table ADD CONSTRAINT $name CHECK ($expression)");
        }
    }

    public function down(): void
    {
        foreach (['supplier_payment_allocations', 'supplier_payments', 'supplier_invoice_items', 'supplier_invoices', 'goods_receipt_items', 'goods_receipts', 'purchase_order_items', 'purchase_orders', 'supplier_addresses', 'suppliers'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
