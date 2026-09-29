<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('sku')->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('item_type');
            $t->string('unit_of_measure');
            $t->decimal('reorder_level', 15, 3)->nullable();
            $t->boolean('is_stock_tracked')->default(true);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('inventory_item_id')->unique()->constrained()->restrictOnDelete();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->string('image_path')->nullable();
            $t->boolean('is_featured')->default(false);
            $t->boolean('is_available_online')->default(true);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('product_prices', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('product_id')->constrained()->restrictOnDelete();
            $t->string('price_type');
            $t->decimal('amount', 15, 2);
            $t->decimal('minimum_quantity', 15, 3)->default(1);
            $t->dateTime('effective_from')->nullable();
            $t->dateTime('effective_until')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index(['product_id', 'price_type', 'is_active', 'minimum_quantity'], 'prices_resolution_index');
        });
        Schema::create('inventory_locations', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('code')->unique();
            $t->string('name');
            $t->string('location_type');
            $t->text('address')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('inventory_stocks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('inventory_item_id')->constrained()->restrictOnDelete();
            $t->foreignId('inventory_location_id')->constrained()->restrictOnDelete();
            $t->decimal('quantity_on_hand', 15, 3)->default(0);
            $t->decimal('quantity_reserved', 15, 3)->default(0);
            $t->decimal('average_unit_cost', 15, 4)->nullable();
            $t->timestamp('updated_at')->nullable();
            $t->unique(['inventory_item_id', 'inventory_location_id']);
        });
        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('inventory_item_id')->constrained()->restrictOnDelete();
            $t->foreignId('from_location_id')->nullable()->constrained('inventory_locations')->restrictOnDelete();
            $t->foreignId('to_location_id')->nullable()->constrained('inventory_locations')->restrictOnDelete();
            $t->string('movement_type');
            $t->decimal('quantity', 15, 3);
            $t->decimal('unit_cost', 15, 4)->nullable();
            $t->decimal('total_cost', 15, 2)->nullable();
            $t->string('reference_type')->nullable();
            $t->unsignedBigInteger('reference_id')->nullable();
            $t->string('reference_number')->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('performed_by')->nullable()->constrained('staff')->restrictOnDelete();
            $t->dateTime('occurred_at');
            $t->timestamps();
            foreach (['inventory_item_id', 'from_location_id', 'to_location_id', 'movement_type'] as $column) {
                $t->index([$column, 'occurred_at']);
            }
            $t->index('occurred_at');
        });
        Schema::create('stock_adjustments', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('adjustment_number')->unique();
            $t->foreignId('inventory_location_id')->constrained()->restrictOnDelete();
            $t->string('reason');
            $t->string('status')->default('DRAFT');
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->constrained('staff')->restrictOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('staff')->restrictOnDelete();
            $t->dateTime('approved_at')->nullable();
            $t->dateTime('posted_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'created_at']);
            $t->index(['inventory_location_id', 'created_at']);
        });
        Schema::create('stock_adjustment_items', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('stock_adjustment_id')->constrained()->restrictOnDelete();
            $t->foreignId('inventory_item_id')->constrained()->restrictOnDelete();
            $t->decimal('system_quantity', 15, 3);
            $t->decimal('counted_quantity', 15, 3);
            $t->decimal('difference_quantity', 15, 3);
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->unique(['stock_adjustment_id', 'inventory_item_id'], 'adjustment_item_unique');
        });
        $this->check('inventory_stocks', 'stock_quantities_valid', 'quantity_on_hand >= 0 AND quantity_reserved >= 0 AND quantity_reserved <= quantity_on_hand');
        $this->check('stock_movements', 'movement_quantity_positive', 'quantity > 0');
        $this->check('stock_movements', 'movement_direction_valid', "(movement_type = 'TRANSFER' AND from_location_id IS NOT NULL AND to_location_id IS NOT NULL AND from_location_id <> to_location_id) OR (movement_type IN ('SUPPLIER_RECEIPT_IN','PRODUCTION_IN','CUSTOMER_RETURN_IN','ADJUSTMENT_IN') AND from_location_id IS NULL AND to_location_id IS NOT NULL) OR (movement_type IN ('PRODUCTION_CONSUMPTION','SALE_OUT','DAMAGE_OUT','ADJUSTMENT_OUT') AND from_location_id IS NOT NULL AND to_location_id IS NULL) OR (movement_type = 'REVERSAL' AND ((from_location_id IS NULL AND to_location_id IS NOT NULL) OR (from_location_id IS NOT NULL AND to_location_id IS NULL)))");
        $this->check('product_prices', 'price_values_positive', 'amount > 0 AND minimum_quantity > 0 AND (effective_until IS NULL OR effective_from IS NULL OR effective_until >= effective_from)');
    }

    private function check(string $table, string $name, string $expression): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite cannot ALTER ADD CHECK; equivalent guards support the isolated test database.
            $columns = Schema::getColumnListing($table);
            $expression = preg_replace_callback('/\b[a-z_]+\b/', fn ($m) => in_array($m[0], $columns, true) ? 'NEW.'.$m[0] : $m[0], $expression);
            foreach (['INSERT', 'UPDATE'] as $operation) {
                DB::unprepared("CREATE TRIGGER {$name}_".strtolower($operation)." BEFORE $operation ON $table WHEN NOT ($expression) BEGIN SELECT RAISE(ABORT, '$name'); END");
            }
        } else {
            DB::statement("ALTER TABLE $table ADD CONSTRAINT $name CHECK ($expression)");
        }
    }

    public function down(): void
    {
        foreach (['stock_adjustment_items', 'stock_adjustments', 'stock_movements', 'inventory_stocks', 'inventory_locations', 'product_prices', 'products', 'inventory_items'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
