<?php

namespace Tests\Feature;

use App\Enums\StockAdjustmentStatus;
use App\Enums\StockMovementType;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryStock;
use App\Models\Staff;
use App\Models\StockMovement;
use App\Services\ActivityLogService;
use App\Services\InventoryService;
use App\Services\StockAdjustmentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryOperationsTest extends TestCase
{
    use RefreshDatabase;

    private Staff $actor;

    private Staff $reviewer;

    private InventoryItem $item;

    private InventoryLocation $location;

    private InventoryService $inventory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actor = Staff::factory()->create();
        $this->actor->assignRole('Super Admin');
        $this->reviewer = Staff::factory()->create();
        $this->reviewer->assignRole('Super Admin');
        $this->item = InventoryItem::factory()->create();
        $this->location = InventoryLocation::factory()->create();
        $this->inventory = app(InventoryService::class);
    }

    private function receive(string $quantity = '100', ?string $cost = '10'): StockMovement
    {
        return $this->inventory->receive($this->item, $this->location, $quantity, $cost, $this->actor);
    }

    private function stock(): InventoryStock
    {
        return InventoryStock::where('inventory_item_id', $this->item->id)->where('inventory_location_id', $this->location->id)->firstOrFail();
    }

    private function draft(string $count)
    {
        return app(StockAdjustmentService::class)->create([
            'inventory_location_id' => $this->location->id, 'reason' => 'PHYSICAL_COUNT',
            'items' => [['inventory_item_id' => $this->item->id, 'counted_quantity' => $count, 'system_quantity' => '999', 'difference_quantity' => '999']],
        ], $this->actor);
    }

    private function approved(string $count)
    {
        $service = app(StockAdjustmentService::class);
        $draft = $this->draft($count);
        $service->submit($draft, $this->actor);

        return $service->approve($draft, $this->reviewer);
    }

    public function test_receipt_and_weighted_costs_are_exact(): void
    {
        $first = $this->receive();
        $this->assertSame('100.000', $this->stock()->quantity_on_hand);
        $this->assertSame('10.0000', $first->unit_cost);
        $this->assertSame('1000.00', $first->total_cost);
        $this->receive('100', '20');
        $this->assertSame('15.0000', $this->stock()->average_unit_cost);
        $this->assertSame('200.000', $this->stock()->quantity_on_hand);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_uneven_decimal_cost_rounding_and_zero_quantity_base(): void
    {
        $this->receive('0.125', '1.2345');
        $this->receive('0.375', '2.3456');
        $this->assertSame('2.0678', $this->stock()->average_unit_cost);
        $out = $this->inventory->outbound($this->item, $this->location, '0.500', $this->actor);
        $this->assertSame('2.0678', $out->unit_cost);
        $this->assertSame('1.03', $out->total_cost);
        $this->receive('1', '9.0001');
        $this->assertSame('9.0001', $this->stock()->average_unit_cost);
    }

    public function test_transfer_moves_once_and_blends_destination_cost(): void
    {
        $this->receive();
        $to = InventoryLocation::factory()->create();
        $this->inventory->receive($this->item, $to, '10', '20', $this->actor);
        $movement = $this->inventory->transfer($this->item, $this->location, $to, '10', $this->actor);
        $this->assertSame(StockMovementType::TRANSFER, $movement->movement_type);
        $this->assertSame('90.000', $this->stock()->quantity_on_hand);
        $destination = InventoryStock::where('inventory_location_id', $to->id)->first();
        $this->assertSame('20.000', $destination->quantity_on_hand);
        $this->assertSame('15.0000', $destination->average_unit_cost);
        $this->assertSame('10.0000', $movement->unit_cost);
        $this->assertSame(1, StockMovement::where('movement_type', 'TRANSFER')->count());
    }

    public function test_reservation_does_not_move_physical_stock(): void
    {
        $this->receive();
        $this->inventory->reserve($this->item, $this->location, '12.125', $this->actor);
        $this->assertSame('100.000', $this->stock()->quantity_on_hand);
        $this->assertSame('87.875', $this->stock()->available_quantity);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->inventory->releaseReservation($this->item, $this->location, '2.125', $this->actor);
        $this->assertSame('10.000', $this->stock()->quantity_reserved);
    }

    public function test_cannot_reserve_above_available(): void
    {
        $this->receive('1');
        $this->expectException(ValidationException::class);
        $this->inventory->reserve($this->item, $this->location, '1.001', $this->actor);
    }

    public function test_cannot_release_above_reserved(): void
    {
        $this->receive();
        $this->expectException(ValidationException::class);
        $this->inventory->releaseReservation($this->item, $this->location, '1', $this->actor);
    }

    public function test_outbound_can_consume_an_explicit_reservation_atomically(): void
    {
        $this->receive('10');
        $this->inventory->reserve($this->item, $this->location, '8', $this->actor);
        $this->inventory->outbound($this->item, $this->location, '8', $this->actor, '8');
        $this->assertSame('2.000', $this->stock()->quantity_on_hand);
        $this->assertSame('0.000', $this->stock()->quantity_reserved);
    }

    public function test_transfer_cannot_use_reserved_stock_and_rolls_back_destination(): void
    {
        $this->receive('10');
        $this->inventory->reserve($this->item, $this->location, '8', $this->actor);
        try {
            $this->inventory->transfer($this->item, $this->location, InventoryLocation::factory()->create(), '3', $this->actor);
            $this->fail();
        } catch (ValidationException) {
        }
        $this->assertSame('10.000', $this->stock()->quantity_on_hand);
        $this->assertDatabaseCount('inventory_stocks', 1);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_same_location_transfer_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->inventory->transfer($this->item, $this->location, $this->location, '1', $this->actor);
    }

    public function test_movement_failure_rolls_back_receipt_and_new_stock(): void
    {
        $this->mock(ActivityLogService::class)->shouldIgnoreMissing();
        $dispatcher = StockMovement::getEventDispatcher();
        StockMovement::setEventDispatcher(clone $dispatcher);
        StockMovement::creating(fn () => throw new \RuntimeException('ledger unavailable'));
        try {
            try {
                $this->receive();
                $this->fail();
            } catch (\RuntimeException $e) {
                $this->assertSame('ledger unavailable', $e->getMessage());
            }
            $this->assertDatabaseCount('inventory_stocks', 0);
            $this->assertDatabaseCount('stock_movements', 0);
        } finally {
            StockMovement::setEventDispatcher($dispatcher);
        }
    }

    public function test_adjustment_snapshots_and_complete_lifecycle(): void
    {
        $this->receive();
        $service = app(StockAdjustmentService::class);
        $draft = $this->draft('90');
        $this->assertSame('100.000', $draft->items->first()->system_quantity);
        $this->assertSame('-10.000', $draft->items->first()->difference_quantity);
        $this->assertStringStartsWith('ADJ-', $draft->adjustment_number);
        $service->submit($draft, $this->actor);
        $service->approve($draft, $this->reviewer);
        $this->assertSame('100.000', $this->stock()->quantity_on_hand);
        $posted = $service->post($draft, $this->actor);
        $this->assertSame(StockAdjustmentStatus::POSTED, $posted->status);
        $this->assertNotNull($posted->posted_at);
        $this->assertSame('90.000', $this->stock()->quantity_on_hand);
        $movement = StockMovement::latest('id')->first();
        $this->assertSame(StockMovementType::ADJUSTMENT_OUT, $movement->movement_type);
        $this->assertSame($draft->id, $movement->reference_id);
        $this->assertSame($draft->adjustment_number, $movement->reference_number);
        $this->assertSame('100.00', $movement->total_cost);
    }

    public function test_positive_and_zero_adjustments(): void
    {
        $service = app(StockAdjustmentService::class);
        $service->post($this->approved('5.125'), $this->actor);
        $this->assertSame('5.125', $this->stock()->quantity_on_hand);
        $this->assertSame(StockMovementType::ADJUSTMENT_IN, StockMovement::first()->movement_type);
        $service->post($this->approved('5.125'), $this->actor);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_stale_count_is_rejected(): void
    {
        $this->receive();
        $adjustment = $this->approved('90');
        $this->inventory->outbound($this->item, $this->location, '5', $this->actor);
        try {
            app(StockAdjustmentService::class)->post($adjustment, $this->actor);
            $this->fail();
        } catch (ValidationException) {
        }
        $this->assertSame('95.000', $this->stock()->quantity_on_hand);
        $this->assertSame(StockAdjustmentStatus::APPROVED, $adjustment->fresh()->status);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_multi_item_posting_rolls_back_every_item(): void
    {
        $second = InventoryItem::factory()->create();
        $this->receive('10');
        $this->inventory->receive($second, $this->location, '10', '1', $this->actor);
        $service = app(StockAdjustmentService::class);
        $draft = $service->create(['inventory_location_id' => $this->location->id, 'reason' => 'LOSS', 'items' => [
            ['inventory_item_id' => $this->item->id, 'counted_quantity' => '9'],
            ['inventory_item_id' => $second->id, 'counted_quantity' => '0'],
        ]], $this->actor);
        $service->submit($draft, $this->actor);
        $service->approve($draft, $this->reviewer);
        $this->inventory->reserve($second, $this->location, '1', $this->actor);
        try {
            $service->post($draft, $this->actor);
            $this->fail();
        } catch (ValidationException) {
        }
        $this->assertSame('10.000', $this->stock()->quantity_on_hand);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertSame(StockAdjustmentStatus::APPROVED, $draft->fresh()->status);
    }

    public function test_self_approval_is_rejected_even_for_owner(): void
    {
        $draft = $this->draft('1');
        app(StockAdjustmentService::class)->submit($draft, $this->actor);
        $this->expectException(ValidationException::class);
        app(StockAdjustmentService::class)->approve($draft, $this->actor);
    }

    public function test_post_twice_rejected(): void
    {
        $service = app(StockAdjustmentService::class);
        $draft = $this->approved('1');
        $service->post($draft, $this->actor);
        $this->expectException(ValidationException::class);
        $service->post($draft, $this->actor);
    }

    public function test_posted_adjustment_cannot_be_updated(): void
    {
        $service = app(StockAdjustmentService::class);
        $draft = $this->approved('1');
        $service->post($draft, $this->actor);
        $this->expectException(ValidationException::class);
        $service->update($draft, [], $this->actor);
    }

    public function test_rejection_and_cancellation_have_no_stock_effect(): void
    {
        $service = app(StockAdjustmentService::class);
        $draft = $this->draft('1');
        $service->submit($draft, $this->actor);
        $this->assertSame(StockAdjustmentStatus::REJECTED, $service->reject($draft, $this->reviewer, 'Recount needed')->status);
        $other = $this->draft('2');
        $this->assertSame(StockAdjustmentStatus::CANCELLED, $service->cancel($other, $this->actor)->status);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseHas('activity_logs', ['event' => 'stock_adjustment.rejected']);
    }

    public function test_inactive_item_and_location_restrict_operations(): void
    {
        $this->location->update(['is_active' => false]);
        $this->expectException(ValidationException::class);
        $this->receive();
    }

    public function test_untracked_item_rejected(): void
    {
        $this->item->update(['is_stock_tracked' => false]);
        $this->expectException(ValidationException::class);
        $this->receive();
    }

    public function test_releasing_existing_reservation_after_deactivation_is_possible(): void
    {
        $this->receive();
        $this->inventory->reserve($this->item, $this->location, '1', $this->actor);
        $this->location->update(['is_active' => false]);
        $this->inventory->releaseReservation($this->item, $this->location, '1', $this->actor);
        $this->assertSame('0.000', $this->stock()->quantity_reserved);
    }

    public function test_unauthorized_primitives_and_adjustments(): void
    {
        $guest = Staff::factory()->create();
        foreach ([
            fn () => $this->inventory->receive($this->item, $this->location, '1', null, $guest),
            fn () => $this->inventory->reserve($this->item, $this->location, '1', $guest),
            fn () => app(StockAdjustmentService::class)->create([], $guest),
            fn () => app(StockAdjustmentService::class)->approve($this->draft('1'), $guest),
            fn () => app(StockAdjustmentService::class)->post($this->draft('1'), $guest),
        ] as $operation) {
            try {
                $operation();
                $this->fail('Authorization required');
            } catch (AuthorizationException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_nonpositive_or_overprecision_quantity_rejected(): void
    {
        foreach (['0', '-1', '0.0001', '1000000000000'] as $quantity) {
            try {
                $this->receive($quantity);
                $this->fail();
            } catch (ValidationException) {
                $this->assertDatabaseCount('stock_movements', 0);
            }
        }
    }
}
