<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InventoryIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public static function invalidStocks(): array
    {
        return [[['quantity_on_hand' => '-1']], [['quantity_reserved' => '-1']], [['quantity_on_hand' => '1', 'quantity_reserved' => '2']]];
    }

    #[DataProvider('invalidStocks')]
    public function test_database_rejects_invalid_stock(array $attributes): void
    {
        $this->expectException(QueryException::class);
        InventoryStock::factory()->create($attributes);
    }

    public function test_stock_pair_unique(): void
    {
        $stock = InventoryStock::factory()->create();
        $this->expectException(QueryException::class);
        InventoryStock::factory()->create($stock->only(['inventory_item_id', 'inventory_location_id']));
    }

    public static function invalidMovements(): array
    {
        return [[['quantity' => '0']], [['quantity' => '-1']], [['movement_type' => 'TRANSFER']], [['movement_type' => 'SALE_OUT']], [['movement_type' => 'UNKNOWN']]];
    }

    #[DataProvider('invalidMovements')]
    public function test_database_rejects_invalid_movement(array $attributes): void
    {
        $attributes += StockMovement::factory()->make()->getAttributes();
        $attributes['uuid'] = (string) Str::uuid();
        $this->expectException(QueryException::class);
        DB::table('stock_movements')->insert($attributes);
    }

    public function test_movement_uuid_unique(): void
    {
        $movement = StockMovement::factory()->create();
        $other = StockMovement::factory()->make();
        $other->uuid = $movement->uuid;
        $this->expectException(QueryException::class);
        $other->save();
    }

    public function test_ledger_update_is_rejected(): void
    {
        $movement = StockMovement::factory()->create();
        $this->expectException(\LogicException::class);
        $movement->update(['quantity' => '2']);
    }

    public function test_ledger_delete_is_rejected(): void
    {
        $movement = StockMovement::factory()->create();
        $this->expectException(\LogicException::class);
        $movement->delete();
    }

    public function test_location_cannot_delete_ledger_history(): void
    {
        $movement = StockMovement::factory()->create();
        $this->expectException(QueryException::class);
        $movement->toLocation->delete();
    }

    public function test_product_item_relationship_unique(): void
    {
        $product = Product::factory()->create();
        $this->expectException(QueryException::class);
        Product::factory()->create(['inventory_item_id' => $product->inventory_item_id]);
    }

    public function test_product_slug_unique(): void
    {
        $product = Product::factory()->create();
        $this->expectException(QueryException::class);
        Product::factory()->create(['slug' => $product->slug]);
    }

    public function test_adjustment_item_unique(): void
    {
        $line = StockAdjustmentItem::factory()->create();
        $this->expectException(QueryException::class);
        StockAdjustmentItem::factory()->create($line->only(['stock_adjustment_id', 'inventory_item_id']));
    }

    public function test_inventory_uuid_unique(): void
    {
        $item = InventoryItem::factory()->create();
        $duplicate = InventoryItem::factory()->make();
        $duplicate->uuid = $item->uuid;
        $this->expectException(QueryException::class);
        $duplicate->save();
    }
}
