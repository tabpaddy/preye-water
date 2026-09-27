<?php

namespace App\Listeners;

use App\Models\Staff;
use Illuminate\Auth\Events\Login;

class RecordStaffLogin
{
    public function handle(Login $event): void
    {
        if ($event->guard === 'staff' && $event->user instanceof Staff) {
            $event->user->forceFill(['last_login_at' => now(), 'last_login_ip' => request()->ip()])->save();
        }
    }
}
