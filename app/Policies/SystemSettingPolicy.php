<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\SystemSetting;

class SystemSettingPolicy
{
    public function viewAny(Staff $user): bool
    {
        return $user->can('view system settings');
    }

    public function view(Staff $user, SystemSetting $setting): bool
    {
        return $user->can('view system settings');
    }

    public function create(Staff $user): bool
    {
        return $user->can('update system settings');
    }

    public function update(Staff $user, SystemSetting $setting): bool
    {
        return $user->can('update system settings');
    }

    public function delete(Staff $user, SystemSetting $setting): bool
    {
        return false;
    }

    public function deleteAny(Staff $user): bool
    {
        return false;
    }
}
