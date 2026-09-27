<?php

namespace Tests\Feature;

use App\Filament\Auth\RequestPasswordReset;
use App\Filament\Auth\ResetPassword;
use App\Models\Staff;
use Filament\Auth\Notifications\ResetPassword as ResetNotification;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class StaffPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Filament::setCurrentPanel(Filament::getPanel('staff'));
        Notification::fake();
    }

    public function test_reset_notification_uses_staff_broker_and_person_email(): void
    {
        $staff = Staff::factory()->create();
        Livewire::test(RequestPasswordReset::class)->fillForm(['staff_number' => $staff->staff_number])->call('request')->assertHasNoFormErrors();
        Notification::assertSentTo($staff, ResetNotification::class, function ($notification) use ($staff) {
            $this->assertStringContainsString('/staff/password-reset/reset', $notification->url);
            $this->assertStringContainsString(urlencode($staff->staff_number), $notification->url);
            $this->assertSame($staff->person->email, $staff->routeNotificationFor('mail'));

            return true;
        });
        $this->assertDatabaseHas('staff_password_reset_tokens', ['email' => $staff->staff_number]);
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_valid_token_resets_password_once_and_updates_timestamp(): void
    {
        $staff = Staff::factory()->create();
        $token = Password::broker('staff')->createToken($staff);
        $this->travel(1)->minute();
        Livewire::test(ResetPassword::class, ['email' => $staff->staff_number, 'token' => $token])->fillForm(['password' => 'new-secure-password', 'passwordConfirmation' => 'new-secure-password'])->call('resetPassword')->assertHasNoFormErrors();
        $this->assertTrue(Hash::check('new-secure-password', $staff->fresh()->password));
        $this->assertTrue($staff->fresh()->password_changed_at->equalTo(now()->startOfSecond()));
        $this->assertDatabaseCount('staff_password_reset_tokens', 0);
        $this->assertDatabaseHas('activity_logs', ['event' => 'staff.password_reset']);
        $this->assertFalse(Password::broker('staff')->tokenExists($staff, $token));
    }

    public function test_invalid_token_does_not_change_password(): void
    {
        $staff = Staff::factory()->create();
        $hash = $staff->password;
        Livewire::test(ResetPassword::class, ['email' => $staff->staff_number, 'token' => 'invalid'])->fillForm(['password' => 'new-secure-password', 'passwordConfirmation' => 'new-secure-password'])->call('resetPassword');
        $this->assertSame($hash, $staff->fresh()->password);
    }

    public function test_inactive_staff_receive_no_reset_email(): void
    {
        $staff = Staff::factory()->create(['status' => 'SUSPENDED']);
        Livewire::test(RequestPasswordReset::class)->fillForm(['staff_number' => $staff->staff_number])->call('request');
        Notification::assertNothingSent();
    }
}
