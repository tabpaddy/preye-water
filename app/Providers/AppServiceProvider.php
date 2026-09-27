<?php

namespace App\Providers;

use App\Enums\StaffStatus;
use App\Models\Staff;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, string $ability) {
            if (! $user instanceof Staff) {
                return null;
            }
            if ($user->status !== StaffStatus::ACTIVE) {
                return false;
            }

            // Named capabilities bypass permissions; policy invariants still apply.
            return str_contains($ability, ' ') && $user->hasRole('Super Admin', 'staff') ? true : null;
        });
        Gate::define('grant super admin', fn ($user) => false);
    }
}
