<?php

namespace Tests\Feature;

use App\Enums\InventoryItemType;
use App\Enums\PriceType;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Staff;
use App\Services\InventoryItemService;
use App\Services\InventoryLocationService;
use App\Services\InventoryService;
use App\Services\PricingService;
use App\Services\ProductService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryCatalogTest extends TestCase
{
    use RefreshDatabase;

    private Staff $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actor = Staff::factory()->create();
        $this->actor->assignRole('Super Admin');
        $this->travelTo(now()->setDate(2026, 9, 29)->startOfDay());
    }

    private function price(Product $product, array $data = []): ProductPrice
    {
        return app(PricingService::class)->create($product, $data + ['price_type' => 'RETAIL', 'amount' => '1250.25', 'minimum_quantity' => '1', 'is_active' => true], $this->actor);
    }

    public function test_product_and_inventory_item_create_without_stock(): void
    {
        $product = app(ProductService::class)->create(['name' => 'Pure Water', 'inventory_item' => ['sku' => 'PURE-01', 'name' => 'Pure Water', 'unit_of_measure' => 'BAG']], $this->actor);
        $this->assertSame(InventoryItemType::FINISHED_GOOD, $product->inventoryItem->item_type);
        $this->assertSame('pure-water-'.$product->inventory_item_id, $product->slug);
        $this->assertNotNull($product->uuid);
        $this->assertNotNull($product->inventoryItem->uuid);
        $this->assertDatabaseCount('inventory_stocks', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_nonproduct_item_and_decimal_reorder_level(): void
    {
        $item = app(InventoryItemService::class)->create(['sku' => 'FILM', 'name' => 'Film', 'item_type' => 'PACKAGING', 'unit_of_measure' => 'KG', 'reorder_level' => '1.125'], $this->actor);
        $this->assertNull($item->product);
        $this->assertSame('1.125', $item->reorder_level);
        $this->assertSame(InventoryItemType::PACKAGING, $item->item_type);
    }

    public function test_sku_uniqueness_includes_archived_items(): void
    {
        $item = InventoryItem::factory()->create(['sku' => 'USED']);
        $item->delete();
        $this->expectException(ValidationException::class);
        app(InventoryItemService::class)->create(['sku' => 'USED', 'name' => 'Film', 'item_type' => 'PACKAGING', 'unit_of_measure' => 'KG'], $this->actor);
    }

    public function test_product_requires_finished_good(): void
    {
        $item = InventoryItem::factory()->create(['item_type' => 'RAW_MATERIAL']);
        $this->expectException(ValidationException::class);
        app(ProductService::class)->create(['inventory_item_id' => $item->id, 'name' => 'Bad product'], $this->actor);
    }

    public function test_linked_item_cannot_be_replaced(): void
    {
        $product = Product::factory()->create();
        $this->expectException(ValidationException::class);
        app(ProductService::class)->update($product, ['name' => 'Changed', 'inventory_item_id' => InventoryItem::factory()->create()->id], $this->actor);
    }

    public function test_each_price_type_resolves_and_money_precision_is_preserved(): void
    {
        $product = Product::factory()->create();
        foreach (PriceType::cases() as $type) {
            $price = $this->price($product, ['price_type' => $type->value]);
            $found = app(PricingService::class)->resolve($product, $type, '1');
            $this->assertSame($price->id, $found->id);
            $this->assertSame('1250.25', $found->amount);
        }
    }

    public function test_quantity_tiers_resolve_deterministically(): void
    {
        $product = Product::factory()->create();
        $base = $this->price($product);
        $tier = $this->price($product, ['minimum_quantity' => '10', 'amount' => '1000']);
        $service = app(PricingService::class);
        $this->assertSame($base->id, $service->resolve($product, PriceType::RETAIL, '9.999')->id);
        $this->assertSame($tier->id, $service->resolve($product, PriceType::RETAIL, '10')->id);
    }

    public function test_price_effective_windows_and_inactive_rows(): void
    {
        $product = Product::factory()->create();
        $past = $this->price($product, ['effective_until' => '2026-09-28 23:59:59']);
        $current = $this->price($product, ['effective_from' => '2026-09-29 00:00:00', 'effective_until' => '2026-09-30 00:00:00']);
        $future = $this->price($product, ['effective_from' => '2026-10-01 00:00:00']);
        $this->price($product, ['minimum_quantity' => '2', 'is_active' => false]);
        $service = app(PricingService::class);
        $this->assertSame($current->id, $service->resolve($product, PriceType::RETAIL, '3')->id);
        $this->assertSame($past->id, $service->resolve($product, PriceType::RETAIL, '1', now()->subDay())->id);
        $this->assertSame($future->id, $service->resolve($product, PriceType::RETAIL, '1', now()->addDays(3))->id);
    }

    public function test_overlap_same_tier_rejected(): void
    {
        $product = Product::factory()->create();
        $this->price($product);
        $this->expectException(ValidationException::class);
        $this->price($product);
    }

    public function test_invalid_price_dates_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->price(Product::factory()->create(), ['effective_from' => '2026-10-01', 'effective_until' => '2026-09-01']);
    }

    public function test_zero_price_and_excess_precision_rejected(): void
    {
        foreach (['0', '-1', '10.001'] as $amount) {
            try {
                $this->price(Product::factory()->create(), ['amount' => $amount]);
                $this->fail();
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_below_minimum_quantity_has_no_price(): void
    {
        $product = Product::factory()->create();
        $this->price($product, ['minimum_quantity' => '10']);
        $this->expectException(ValidationException::class);
        app(PricingService::class)->resolve($product, PriceType::RETAIL, '9');
    }

    public function test_inactive_product_and_archived_item_are_not_catalog_available(): void
    {
        $product = Product::factory()->create(['is_featured' => true]);
        $this->price($product);
        $this->assertSame(1, Product::availableOnline()->featured()->count());
        $product->inventoryItem->delete();
        $this->assertSame(0, Product::active()->count());
        $this->expectException(ValidationException::class);
        app(PricingService::class)->resolve($product, PriceType::RETAIL, '1');
    }

    public function test_archive_preserves_ledger_relationships(): void
    {
        $item = InventoryItem::factory()->create();
        $location = InventoryLocation::factory()->create();
        $inventory = app(InventoryService::class);
        $move = $inventory->receive($item, $location, '1', '10', $this->actor);
        $inventory->outbound($item, $location, '1', $this->actor);
        app(InventoryItemService::class)->archive($item, $this->actor);
        $this->assertSoftDeleted($item);
        $this->assertSame($item->id, $move->fresh()->inventoryItem->id);
    }

    public function test_archive_rejects_nonzero_stock(): void
    {
        $item = InventoryItem::factory()->create();
        app(InventoryService::class)->receive($item, InventoryLocation::factory()->create(), '1', null, $this->actor);
        $this->expectException(ValidationException::class);
        app(InventoryItemService::class)->archive($item, $this->actor);
    }

    public function test_low_stock_aggregates_active_locations_including_no_balance(): void
    {
        $item = InventoryItem::factory()->create(['reorder_level' => '10']);
        $empty = InventoryItem::factory()->create(['reorder_level' => '2']);
        $a = InventoryLocation::factory()->create();
        $b = InventoryLocation::factory()->create();
        $inventory = app(InventoryService::class);
        $inventory->receive($item, $a, '8', null, $this->actor);
        $inventory->receive($item, $b, '8', null, $this->actor);
        $this->assertFalse(InventoryItem::lowStock()->whereKey($item->id)->exists());
        $inventory->reserve($item, $a, '6', $this->actor);
        $this->assertTrue(InventoryItem::lowStock()->whereKey($item->id)->exists());
        $this->assertTrue(InventoryItem::lowStock()->whereKey($empty->id)->exists());
        $b->update(['is_active' => false]);
        $this->assertTrue(InventoryItem::lowStock()->whereKey($item->id)->exists());
    }

    public function test_location_validation_and_audit(): void
    {
        $service = app(InventoryLocationService::class);
        $location = $service->create(['code' => 'WH-MAIN', 'name' => 'Warehouse', 'location_type' => 'WAREHOUSE'], $this->actor);
        $this->assertSame('WAREHOUSE', $location->location_type->value);
        $this->assertDatabaseHas('activity_logs', ['event' => 'inventory_location.created']);
        $this->expectException(ValidationException::class);
        $service->create(['code' => 'WH-MAIN', 'name' => 'Duplicate', 'location_type' => 'WAREHOUSE'], $this->actor);
    }

    public function test_product_update_requires_permission(): void
    {
        $this->expectException(AuthorizationException::class);
        app(ProductService::class)->update(Product::factory()->create(), ['name' => 'Forged'], Staff::factory()->create());
    }
}
