<?php

namespace App\Listeners;

use App\Models\Staff;
use App\Services\ActivityLogService;
use Illuminate\Auth\Events\PasswordReset;

class RecordStaffPasswordReset
{
    public function handle(PasswordReset $event): void
    {
        if ($event->user instanceof Staff) {
            $event->user->forceFill(['password_changed_at' => now()])->save();
            app(ActivityLogService::class)->record('staff.password_reset', $event->user, $event->user, 'Staff password reset');
        }
    }
}
