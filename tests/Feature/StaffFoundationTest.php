<?php

namespace Tests\Feature;

use App\Enums\StaffStatus;
use App\Filament\Auth\Login;
use App\Filament\Resources\Staff\Pages\CreateStaff;
use App\Filament\Resources\Staff\Pages\EditStaff;
use App\Models\ActivityLog;
use App\Models\Staff;
use App\Models\User;
use App\Services\StaffRoleService;
use App\Services\StaffService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class StaffFoundationTest extends TestCase
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
        $staff = Staff::factory()->create();
        $staff->assignRole('Super Admin');

        return $staff;
    }

    private function data(): array
    {
        return ['first_name' => 'Preye', 'last_name' => 'Operator', 'email' => 'operator@example.com', 'password' => 'secure-password-123', 'password_confirmation' => 'secure-password-123'];
    }

    public function test_active_staff_can_login_and_successful_login_is_tracked(): void
    {
        $staff = Staff::factory()->create();
        Livewire::test(Login::class)->fillForm(['staff_number' => $staff->staff_number, 'password' => 'test-password-123'])->call('authenticate')->assertHasNoFormErrors();
        $this->assertAuthenticatedAs($staff, 'staff');
        $this->assertNotNull($staff->fresh()->last_login_at);
        $this->assertNotNull($staff->fresh()->last_login_ip);
        $this->assertGuest('web');
    }

    public function test_invalid_password_is_rejected_without_login_tracking(): void
    {
        $staff = Staff::factory()->create();
        Livewire::test(Login::class)->fillForm(['staff_number' => $staff->staff_number, 'password' => 'incorrect'])->call('authenticate')->assertHasFormErrors(['staff_number']);
        $this->assertGuest('staff');
        $this->assertNull($staff->fresh()->last_login_at);
    }

    public static function blockedStatuses(): array
    {
        return [['INACTIVE'], ['SUSPENDED'], ['TERMINATED']];
    }

    #[DataProvider('blockedStatuses')]
    public function test_nonactive_staff_cannot_login_or_keep_panel_access(string $status): void
    {
        $staff = Staff::factory()->create(['status' => $status]);
        $staff->assignRole('Super Admin');
        Livewire::test(Login::class)->fillForm(['staff_number' => $staff->staff_number, 'password' => 'test-password-123'])->call('authenticate')->assertHasFormErrors(['staff_number']);
        $this->assertGuest('staff');
        $this->actingAs($staff, 'staff')->get('/staff')->assertForbidden();
        $this->assertFalse($staff->can('view staff'));
    }

    public function test_default_web_user_cannot_access_staff_panel(): void
    {
        $this->actingAs(User::factory()->create(), 'web')->get('/staff')->assertRedirect('/staff/login');
        $this->assertGuest('staff');
    }

    public function test_service_creates_identity_account_number_role_and_audit(): void
    {
        $admin = $this->admin();
        $data = $this->data() + ['roles' => [Role::findByName('Manager', 'staff')->id]];
        $staff = app(StaffService::class)->create($data, $admin);
        $second = app(StaffService::class)->create($this->data(), $admin);
        $this->assertSame('Preye', $staff->person->first_name);
        $this->assertTrue(Hash::check($data['password'], $staff->password));
        $this->assertStringStartsWith('STF-'.now('Africa/Lagos')->year.'-', $staff->staff_number);
        $this->assertNotSame($staff->staff_number, $second->staff_number);
        $this->assertTrue($staff->hasRole('Manager'));
        $this->assertDatabaseHas('activity_logs', ['event' => 'staff.created', 'subject_id' => $staff->id]);
        $this->assertDatabaseHas('activity_logs', ['event' => 'staff.roles_changed', 'subject_id' => $staff->id]);
        $this->assertStringNotContainsString($data['password'], ActivityLog::all()->toJson());
        $this->assertStringNotContainsString($staff->password, ActivityLog::all()->toJson());
    }

    public function test_role_authorization_failure_rolls_back_identity_account_and_number(): void
    {
        $actor = Staff::factory()->create();
        $actor->givePermissionTo('create staff');
        try {
            app(StaffService::class)->create($this->data() + ['roles' => [Role::findByName('Manager', 'staff')->id]], $actor);
            $this->fail('Expected authorization failure');
        } catch (AuthorizationException) {
        }
        $this->assertDatabaseCount('staff', 1);
        $this->assertDatabaseCount('people', 1);
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertDatabaseHas('number_sequences', ['key' => 'STAFF', 'current_number' => 0]);
    }

    public function test_updates_preserve_blank_password_and_change_password_and_status_explicitly(): void
    {
        $admin = $this->admin();
        $staff = Staff::factory()->create();
        $hash = $staff->password;
        $service = app(StaffService::class);
        $staff = $service->update($staff, ['first_name' => 'Changed', 'password' => ''], $admin);
        $this->assertSame('Changed', $staff->person->first_name);
        $this->assertSame($hash, $staff->password);
        $this->travel(1)->day();
        $staff = $service->update($staff, ['password' => 'another-password-123', 'password_confirmation' => 'another-password-123', 'status' => 'SUSPENDED'], $admin);
        $this->assertTrue(Hash::check('another-password-123', $staff->password));
        $this->assertTrue($staff->password_changed_at->isToday());
        $this->assertSame(StaffStatus::SUSPENDED, $staff->status);
        $this->assertDatabaseHas('activity_logs', ['event' => 'staff.status_changed']);
    }

    public function test_super_admin_bypass_and_manager_capabilities(): void
    {
        $this->assertTrue($this->admin()->can('future capability'));
        $manager = Staff::factory()->create();
        $manager->assignRole('Manager');
        $this->assertTrue($manager->can('view staff'));
        $this->assertFalse($manager->can('update business settings'));
        $this->assertFalse($manager->can('manage staff roles'));
    }

    public function test_staff_resource_is_authorized_server_side(): void
    {
        $staff = Staff::factory()->create();
        $this->actingAs($staff, 'staff')->get('/staff/staff')->assertForbidden();
        $staff->givePermissionTo('view staff');
        $this->get('/staff/staff')->assertOk();
        $this->get('/staff/staff/create')->assertForbidden();
    }

    public function test_filament_create_and_edit_use_staff_service(): void
    {
        $this->actingAs($this->admin(), 'staff');
        Livewire::test(CreateStaff::class)->fillForm($this->data() + ['status' => 'ACTIVE', 'roles' => []])->call('create')->assertHasNoFormErrors();
        $staff = Staff::whereHas('person', fn ($q) => $q->where('first_name', 'Preye'))->firstOrFail();
        Livewire::test(EditStaff::class, ['record' => $staff->uuid])->assertFormSet(['password' => ''])->fillForm(['first_name' => 'Updated'])->call('save')->assertHasNoFormErrors();
        $this->assertSame('Updated', $staff->fresh()->person->first_name);
        $this->assertDatabaseHas('activity_logs', ['event' => 'staff.updated', 'subject_id' => $staff->id]);
    }

    public function test_cross_guard_role_is_rejected(): void
    {
        $admin = $this->admin();
        $role = Role::create(['name' => 'Web role', 'guard_name' => 'web']);
        $this->expectException(ValidationException::class);
        app(StaffRoleService::class)->syncRoles($admin, [$role->id], $admin);
    }

    public function test_bootstrap_creates_first_admin_and_cannot_be_repeated(): void
    {
        $staff = app(StaffService::class)->bootstrap($this->data());
        $this->assertTrue($staff->hasRole('Super Admin', 'staff'));
        $this->expectException(HttpException::class);
        app(StaffService::class)->bootstrap($this->data());
    }
}
