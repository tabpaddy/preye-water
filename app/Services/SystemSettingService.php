<?php

namespace App\Services;

use App\Enums\SystemSettingType;
use App\Models\Staff;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SystemSettingService
{
    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember($this->cacheKey($group, $key), 3600, fn () => SystemSetting::where('group', $group)->where('key', $key)->first());

        return $setting ? match ($setting->type) {
            SystemSettingType::STRING, SystemSettingType::DECIMAL => $setting->value,
            SystemSettingType::INTEGER => (int) $setting->value,
            SystemSettingType::BOOLEAN => (bool) $setting->value,
            SystemSettingType::JSON => json_decode($setting->value, true, 512, JSON_THROW_ON_ERROR),
        } : $default;
    }

    public function set(array $data, Staff $actor): SystemSetting
    {
        Gate::forUser($actor)->authorize('update system settings');
        $data = Validator::make($data, ['group' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/'], 'key' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/'], 'type' => ['required', Rule::enum(SystemSettingType::class)], 'value' => ['present'], 'is_public' => ['sometimes', 'boolean'], 'description' => ['nullable', 'string', 'max:2000']])->validate();
        if (preg_match('/password|token|secret|credential|api.?key/i', $data['group'].'.'.$data['key'])) {
            throw ValidationException::withMessages(['key' => 'Secrets belong in environment configuration.']);
        }
        $type = SystemSettingType::from($data['type']);
        $rule = match ($type) {
            SystemSettingType::STRING => ['nullable', 'string', 'max:10000'],
            SystemSettingType::INTEGER => ['required', 'integer'],
            SystemSettingType::DECIMAL => ['required', 'regex:/^-?\d+(\.\d+)?$/'],
            SystemSettingType::BOOLEAN => ['required', 'boolean'],
            SystemSettingType::JSON => ['required', 'json'],
        };
        Validator::make($data, ['value' => $rule])->validate();
        $data['value'] = $type === SystemSettingType::BOOLEAN ? (string) (int) $data['value'] : $data['value'];

        return DB::transaction(function () use ($data, $actor) {
            $record = SystemSetting::firstOrNew(['group' => $data['group'], 'key' => $data['key']]);
            $old = $record->toArray();
            $record->fill($data)->save();
            app(ActivityLogService::class)->record('system.settings_updated', $record, $actor, 'System setting updated', $old, $data);
            DB::afterCommit(fn () => Cache::forget($this->cacheKey($record->group, $record->key)));

            return $record;
        });
    }

    private function cacheKey(string $group, string $key): string
    {
        return 'system.settings.'.hash('sha256', $group."\0".$key);
    }
}
