<?php

namespace Tests\Feature;

use App\Enums\StockAdjustmentStatus;
use App\Filament\Resources\InventoryItems\Pages\CreateInventoryItem;
use App\Filament\Resources\InventoryStocks\InventoryStockResource;
use App\Filament\Resources\InventoryStocks\Pages\ListInventoryStocks;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Filament\Resources\Products\RelationManagers\ProductPricesRelationManager;
use App\Filament\Resources\StockAdjustments\Pages\CreateStockAdjustment;
use App\Filament\Resources\StockAdjustments\Pages\EditStockAdjustment;
use App\Filament\Resources\StockAdjustments\Pages\ListStockAdjustments;
use App\Filament\Resources\StockAdjustments\StockAdjustmentResource;
use App\Filament\Resources\StockMovements\StockMovementResource;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Staff;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryInterfaceTest extends TestCase
{
    use RefreshDatabase;

    private Staff $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Filament::setCurrentPanel(Filament::getPanel('staff'));
        $this->admin = Staff::factory()->create();
        $this->admin->assignRole('Super Admin');
        $this->actingAs($this->admin, 'staff');
    }

    public function test_all_inventory_lists_render(): void
    {
        InventoryItem::factory()->create();
        Product::factory()->create();
        InventoryLocation::factory()->create();
        InventoryStock::factory()->create();
        StockMovement::factory()->create();
        StockAdjustment::factory()->create();
        foreach (['inventory-items', 'products', 'inventory-locations', 'inventory-stocks', 'stock-movements', 'stock-adjustments'] as $slug) {
            $this->get('/staff/'.$slug)->assertOk();
        }
    }

    public function test_inventory_and_product_creation_call_services(): void
    {
        Livewire::test(CreateInventoryItem::class)->fillForm([
            'sku' => 'UI-01', 'name' => 'Water Bag', 'item_type' => 'FINISHED_GOOD', 'unit_of_measure' => 'BAG', 'is_stock_tracked' => true, 'is_active' => true,
        ])->call('create')->assertHasNoFormErrors();
        $item = InventoryItem::where('sku', 'UI-01')->firstOrFail();
        Livewire::test(CreateProduct::class)->fillForm([
            'inventory_item_id' => $item->id, 'name' => 'Water Bag', 'is_active' => true, 'is_available_online' => true, 'is_featured' => false,
        ])->call('create')->assertHasNoFormErrors();
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('inventory_stocks', 0);
        $this->assertDatabaseHas('activity_logs', ['event' => 'product.created']);
    }

    public function test_unauthorized_staff_cannot_access_catalog_or_inventory(): void
    {
        $this->flushSession();
        $this->actingAs(Staff::factory()->create(), 'staff');
        foreach (['inventory-items', 'products', 'inventory-stocks', 'stock-movements', 'stock-adjustments'] as $slug) {
            $this->get('/staff/'.$slug)->assertForbidden();
        }
    }

    public function test_view_stock_permission_and_read_only_resources(): void
    {
        $staff = Staff::factory()->create();
        $staff->givePermissionTo('view inventory stocks');
        $this->flushSession();
        $this->actingAs($staff, 'staff');
        $this->get('/staff/inventory-stocks')->assertOk();
        $this->assertFalse(InventoryStockResource::canCreate());
        $this->assertArrayNotHasKey('edit', InventoryStockResource::getPages());
        $this->assertArrayNotHasKey('create', StockMovementResource::getPages());
        $this->assertArrayNotHasKey('edit', StockMovementResource::getPages());
    }

    public function test_product_edit_requires_permission(): void
    {
        $product = Product::factory()->create();
        $viewer = Staff::factory()->create();
        $viewer->givePermissionTo('view products');
        $this->flushSession();
        $this->actingAs($viewer, 'staff');
        Livewire::test(EditProduct::class, ['record' => $product->uuid])->assertForbidden();
    }

    public function test_price_relation_calls_pricing_service(): void
    {
        $product = Product::factory()->create();
        Livewire::test(ProductPricesRelationManager::class, ['ownerRecord' => $product, 'pageClass' => ViewProduct::class])
            ->callAction(TestAction::make('create')->table(), ['price_type' => 'RETAIL', 'amount' => '120.25', 'minimum_quantity' => '1', 'is_active' => true])
            ->assertHasNoActionErrors();
        $this->assertDatabaseHas('product_prices', ['product_id' => $product->id, 'amount' => '120.25']);
        $this->assertDatabaseHas('activity_logs', ['event' => 'product_price.created']);
    }

    public function test_adjustment_ui_lifecycle_and_finalized_edit_denial(): void
    {
        $item = InventoryItem::factory()->create();
        $location = InventoryLocation::factory()->create();
        Livewire::test(CreateStockAdjustment::class)->fillForm([
            'inventory_location_id' => $location->id, 'reason' => 'DATA_CORRECTION',
            'items' => [['inventory_item_id' => $item->id, 'counted_quantity' => '5.125']],
        ])->call('create')->assertHasNoFormErrors();
        $draft = StockAdjustment::firstOrFail();
        Livewire::test(ListStockAdjustments::class)->callAction(TestAction::make('submit')->table($draft))->assertHasNoActionErrors();
        $reviewer = Staff::factory()->create();
        $reviewer->givePermissionTo(['view stock adjustments', 'approve stock adjustments', 'post stock adjustments']);
        $this->flushSession();
        $this->actingAs($reviewer, 'staff');
        Livewire::test(ListStockAdjustments::class)->callAction(TestAction::make('approve')->table($draft))->assertHasNoActionErrors();
        Livewire::test(ListStockAdjustments::class)->callAction(TestAction::make('post')->table($draft))->assertHasNoActionErrors();
        $this->assertSame(StockAdjustmentStatus::POSTED, $draft->fresh()->status);
        $this->assertDatabaseHas('inventory_stocks', ['inventory_item_id' => $item->id, 'quantity_on_hand' => '5.125']);
        $this->assertDatabaseHas('stock_movements', ['reference_id' => $draft->id]);
        $this->flushSession();
        $this->actingAs($this->admin, 'staff');
        Livewire::test(EditStockAdjustment::class, ['record' => $draft->uuid])->assertForbidden();
    }

    public function test_viewer_cannot_invoke_approval_or_posting_actions(): void
    {
        $record = StockAdjustment::factory()->create(['status' => 'PENDING_APPROVAL']);
        $viewer = Staff::factory()->create();
        $viewer->givePermissionTo('view stock adjustments');
        $this->flushSession();
        $this->actingAs($viewer, 'staff');
        Livewire::test(ListStockAdjustments::class)->assertActionHidden(TestAction::make('approve')->table($record))
            ->mountAction(TestAction::make('approve')->table($record))->callMountedAction();
        $this->assertSame(StockAdjustmentStatus::PENDING_APPROVAL, $record->fresh()->status);
        $record->update(['status' => 'APPROVED']);
        Livewire::test(ListStockAdjustments::class)->assertActionHidden(TestAction::make('post')->table($record))
            ->mountAction(TestAction::make('post')->table($record))->callMountedAction();
        $this->assertSame(StockAdjustmentStatus::APPROVED, $record->fresh()->status);
        $this->assertFalse(StockAdjustmentResource::canCreate());
    }

    public function test_transfer_action_calls_inventory_service(): void
    {
        $item = InventoryItem::factory()->create();
        $from = InventoryLocation::factory()->create();
        $to = InventoryLocation::factory()->create();
        app(InventoryService::class)->receive($item, $from, '10', '2', $this->admin);
        $stock = InventoryStock::first();
        Livewire::test(ListInventoryStocks::class)->callAction(TestAction::make('transfer')->table($stock), ['to_location_id' => $to->id, 'quantity' => '2.125'])->assertHasNoActionErrors();
        $this->assertSame('7.875', $stock->fresh()->quantity_on_hand);
        $this->assertDatabaseHas('stock_movements', ['movement_type' => 'TRANSFER', 'quantity' => '2.125']);
    }
}
