<?php

namespace Tests\Feature;

use App\Models\NumberSequence;
use App\Services\NumberSequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NumberSequenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_yearly_increment_padding_and_reset(): void
    {
        $this->travelTo(now()->setDate(2026, 1, 2));
        NumberSequence::create(['key' => 'STAFF', 'prefix' => 'STF', 'padding' => 6, 'reset_frequency' => 'YEARLY']);
        $service = app(NumberSequenceService::class);
        $this->assertSame('STF-2026-000001', $service->next('STAFF'));
        $this->assertSame('STF-2026-000002', $service->next('STAFF'));
        $this->travel(1)->year();
        $this->assertSame('STF-2027-000001', $service->next('STAFF'));
        $this->assertDatabaseCount('number_sequences', 1);
    }

    public function test_monthly_and_never_reset(): void
    {
        $this->travelTo(now()->setDate(2026, 1, 2));
        NumberSequence::create(['key' => 'MONTH', 'prefix' => 'M', 'padding' => 3, 'reset_frequency' => 'MONTHLY']);
        NumberSequence::create(['key' => 'NEVER', 'prefix' => 'N', 'padding' => 2, 'reset_frequency' => 'NEVER']);
        $service = app(NumberSequenceService::class);
        $this->assertSame('M-2026-01-001', $service->next('MONTH'));
        $this->assertSame('N-01', $service->next('NEVER'));
        $this->travel(1)->month();
        $this->assertSame('M-2026-02-001', $service->next('MONTH'));
        $this->assertSame('N-02', $service->next('NEVER'));
    }

    public function test_outer_rollback_restores_number(): void
    {
        $this->seed();
        DB::beginTransaction();
        app(NumberSequenceService::class)->next('STAFF');
        DB::rollBack();
        $this->assertDatabaseHas('number_sequences', ['key' => 'STAFF', 'current_number' => 0]);
    }
}
