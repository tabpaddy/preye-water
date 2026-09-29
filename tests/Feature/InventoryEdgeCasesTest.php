<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Staff;
use App\Models\StockMovement;
use App\Services\InventoryService;
use App\Services\ProductService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    private Staff $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actor = Staff::factory()->create();
        $this->actor->assignRole('Super Admin');
    }

    public function test_float_input_is_rejected_instead_of_truncated(): void
    {
        $this->expectException(ValidationException::class);
        app(InventoryService::class)->receive(InventoryItem::factory()->create(), InventoryLocation::factory()->create(), 2.125, '1', $this->actor);
    }

    public function test_unknown_source_cost_is_not_replaced_by_destination_cost(): void
    {
        $service = app(InventoryService::class);
        $item = InventoryItem::factory()->create();
        $from = InventoryLocation::factory()->create();
        $to = InventoryLocation::factory()->create();
        $service->receive($item, $from, '1', null, $this->actor);
        $service->receive($item, $to, '1', '10', $this->actor);
        $move = $service->transfer($item, $from, $to, '1', $this->actor);
        $this->assertNull($move->unit_cost);
        $this->assertNull($move->total_cost);
        $this->assertNull(InventoryStock::where('inventory_location_id', $to->id)->first()->average_unit_cost);
    }

    public function test_transfer_ledger_failure_rolls_back_both_balances(): void
    {
        $service = app(InventoryService::class);
        $item = InventoryItem::factory()->create();
        $from = InventoryLocation::factory()->create();
        $to = InventoryLocation::factory()->create();
        $service->receive($item, $from, '10', '2', $this->actor);
        $dispatcher = StockMovement::getEventDispatcher();
        StockMovement::setEventDispatcher(clone $dispatcher);
        StockMovement::creating(fn () => throw new \RuntimeException('ledger failure'));
        try {
            try {
                $service->transfer($item, $from, $to, '1', $this->actor);
                $this->fail();
            } catch (\RuntimeException $e) {
                $this->assertSame('ledger failure', $e->getMessage());
            }
            $this->assertSame('10.000', $service->availableQuantity($item, $from));
            $this->assertDatabaseCount('inventory_stocks', 1);
            $this->assertDatabaseCount('stock_movements', 1);
        } finally {
            StockMovement::setEventDispatcher($dispatcher);
        }
    }

    public function test_inactive_item_cannot_receive(): void
    {
        $this->expectException(ValidationException::class);
        app(InventoryService::class)->receive(InventoryItem::factory()->create(['is_active' => false]), InventoryLocation::factory()->create(), '1', null, $this->actor);
    }

    public function test_product_image_upload_through_filament_is_validated_and_stored(): void
    {
        Storage::fake('public');
        Filament::setCurrentPanel(Filament::getPanel('staff'));
        $this->actingAs($this->actor, 'staff');
        $product = Product::factory()->create();
        $image = UploadedFile::fake()->createWithContent('bottle.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWZkAAAAASUVORK5CYII='));
        Livewire::test(ViewProduct::class, ['record' => $product->uuid])->callAction('replaceImage', ['image' => $image])->assertHasNoActionErrors();
        $path = $product->fresh()->image_path;
        $this->assertStringStartsWith('products/', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertDatabaseHas('activity_logs', ['event' => 'product.image_updated']);
    }

    public function test_arbitrary_product_upload_is_rejected(): void
    {
        Storage::fake('public');
        $this->expectException(ValidationException::class);
        app(ProductService::class)->replaceImage(Product::factory()->create(),UploadedFile::fake()->create('script.php',10,'application/x-php'),$this->actor);
    }
}
