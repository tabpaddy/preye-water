<?php

namespace Tests\Feature;

use App\Filament\Pages\BusinessSettings;
use App\Models\Staff;
use App\Services\BusinessSettingService;
use App\Services\SystemSettingService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Filament::setCurrentPanel(Filament::getPanel('staff'));
    }

    private function admin(): Staff
    {
        $s = Staff::factory()->create();
        $s->assignRole('Super Admin');

        return $s;
    }

    public function test_business_cache_update_and_audit(): void
    {
        $service = app(BusinessSettingService::class);
        $this->assertSame('NGN', $service->get()->currency);
        $this->assertTrue(Cache::has('business.settings'));
        $service->update(['name' => 'Updated'], ['currency' => 'USD'], $this->admin());
        $this->assertFalse(Cache::has('business.settings'));
        $this->assertSame('USD', $service->get()->currency);
        $this->assertSame('Updated', $service->get()->business->name);
        $this->assertDatabaseHas('activity_logs', ['event' => 'business.settings_updated']);
    }

    public function test_business_page_save_and_authorization(): void
    {
        $this->actingAs($this->admin(), 'staff')->get('/staff/business-settings')->assertOk();
        Livewire::test(BusinessSettings::class)->fillForm(['business.name' => 'Water Factory'])->call('save')->assertHasNoFormErrors();
        $this->assertDatabaseHas('businesses', ['name' => 'Water Factory']);
        $this->flushSession();
        $staff = Staff::factory()->create();
        $this->actingAs($staff, 'staff')->get('/staff/business-settings')->assertForbidden();
        $staff->givePermissionTo('view business settings');
        $this->get('/staff/business-settings')->assertOk();
        Livewire::test(BusinessSettings::class)->call('save')->assertForbidden();
    }

    public function test_business_rejects_secrets(): void
    {
        $this->expectException(ValidationException::class);
        app(BusinessSettingService::class)->update(['name' => 'Water'], ['secret_key' => 'secret'], $this->admin());
    }

    public function test_system_types_cache_invalidation_and_audit(): void
    {
        $service = app(SystemSettingService::class);
        $actor = $this->admin();
        foreach ([['STRING', 'hello', 'hello'], ['INTEGER', '42', 42], ['DECIMAL', '12.34', '12.34'], ['BOOLEAN', '0', false], ['BOOLEAN', '1', true], ['JSON', '{"flag":true}', ['flag' => true]]] as [$type,$value,$expected]) {
            $service->set(['group' => 'general', 'key' => 'test', 'type' => $type, 'value' => $value], $actor);
            $this->assertSame($expected, $service->get('general', 'test'));
        }
        $this->assertDatabaseCount('system_settings', 1);
        $this->assertDatabaseHas('activity_logs', ['event' => 'system.settings_updated']);
    }

    public function test_system_rejects_secret_keys(): void
    {
        $this->expectException(ValidationException::class);
        app(SystemSettingService::class)->set(['group' => 'general', 'key' => 'api_key', 'type' => 'STRING', 'value' => 'secret'], $this->admin());
    }

    public function test_system_requires_permission(): void
    {
        $this->expectException(AuthorizationException::class);
        app(SystemSettingService::class)->set(['group' => 'general', 'key' => 'flag', 'type' => 'BOOLEAN', 'value' => '1'], Staff::factory()->create());
    }

    public function test_seeders_are_idempotent(): void
    {
        $this->seed();
        foreach (['businesses' => 1, 'business_settings' => 1, 'number_sequences' => 8, 'permissions' => 89, 'roles' => 2, 'staff' => 0] as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }
    }
}
