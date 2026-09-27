<?php

namespace Tests\Feature;

use App\Filament\Resources\SystemSettings\Pages\CreateSystemSetting;
use App\Filament\Resources\SystemSettings\Pages\EditSystemSetting;
use App\Models\Staff;
use App\Models\SystemSetting;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SystemSettingsInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_create_edit_and_route_permissions(): void
    {
        $this->seed();
        Filament::setCurrentPanel(Filament::getPanel('staff'));
        $admin = Staff::factory()->create();
        $admin->assignRole('Super Admin');
        $this->actingAs($admin, 'staff')->get('/staff/system-settings')->assertOk();
        Livewire::test(CreateSystemSetting::class)->fillForm(['group' => 'general', 'key' => 'flag', 'type' => 'BOOLEAN', 'value' => '1', 'is_public' => false])->call('create')->assertHasNoFormErrors();
        $setting = SystemSetting::firstOrFail();
        Livewire::test(EditSystemSetting::class, ['record' => $setting->id])->fillForm(['value' => '0'])->call('save')->assertHasNoFormErrors();
        $this->assertSame('0', $setting->fresh()->value);
        $this->assertDatabaseCount('activity_logs', 2);
        $this->flushSession();
        $staff = Staff::factory()->create();
        $this->actingAs($staff, 'staff')->get('/staff/system-settings')->assertForbidden();
        $staff->givePermissionTo('view system settings');
        $this->get('/staff/system-settings')->assertOk();
        $this->get('/staff/system-settings/'.$setting->id.'/edit')->assertForbidden();
    }

    public function test_invalid_boolean_does_not_create_setting_or_audit(): void
    {
        $this->seed();
        Filament::setCurrentPanel(Filament::getPanel('staff'));
        $admin = Staff::factory()->create();
        $admin->assignRole('Super Admin');
        $this->actingAs($admin, 'staff');
        Livewire::test(CreateSystemSetting::class)->fillForm(['group' => 'general', 'key' => 'flag', 'type' => 'BOOLEAN', 'value' => 'perhaps'])->call('create')->assertHasErrors();
        $this->assertDatabaseCount('system_settings', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }
}
