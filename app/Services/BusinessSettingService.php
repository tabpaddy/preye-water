<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessSetting;
use App\Models\Staff;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BusinessSettingService
{
    public function get(): BusinessSetting
    {
        return Cache::remember('business.settings', 3600, fn () => BusinessSetting::with('business')->firstOrFail());
    }

    public function update(array $business, array $settings, Staff $actor): BusinessSetting
    {
        Gate::forUser($actor)->authorize('update business settings');
        $businessRules = array_fill_keys(array_diff((new Business)->getFillable(), ['logo_path', 'is_active']), ['nullable', 'string', 'max:255']);
        $businessRules['name'] = ['required', 'string', 'max:255'];
        $businessRules['email'] = ['nullable', 'email'];
        $businessRules['country'] = ['sometimes', 'required', 'string', 'max:255'];
        $rules = ['currency' => ['sometimes', 'regex:/^[A-Z]{3}$/'], 'timezone' => ['sometimes', 'timezone'], 'date_format' => ['sometimes', 'in:d/m/Y,Y-m-d,m/d/Y'], 'time_format' => ['sometimes', 'in:H:i,h:i A'], 'default_payment_provider' => ['nullable', 'in:paystack']];
        foreach (['invoice', 'receipt', 'order', 'sale'] as $prefix) {
            $rules[$prefix.'_prefix'] = ['sometimes', 'regex:/^[A-Z0-9]{1,10}$/'];
        }
        foreach ((new BusinessSetting)->getCasts() as $field => $cast) {
            if ($cast === 'boolean') {
                $rules[$field] = ['sometimes', 'boolean'];
            }
        }
        if (array_diff(array_keys($business), array_keys($businessRules)) || array_diff(array_keys($settings), array_keys($rules))) {
            throw ValidationException::withMessages(['settings' => 'Unsupported settings fields. Secrets belong in environment configuration.']);
        }
        $business = Validator::make($business, $businessRules)->validate();
        $settings = Validator::make($settings, $rules)->validate();

        return DB::transaction(function () use ($business, $settings, $actor) {
            $record = BusinessSetting::lockForUpdate()->firstOrFail();
            $old = ['business' => $record->business->toArray(), 'settings' => $record->toArray()];
            $record->business->update($business);
            $record->update($settings);
            app(ActivityLogService::class)->record('business.settings_updated', $record, $actor, 'Business settings updated', $old, ['business' => $business, 'settings' => $settings]);
            DB::afterCommit(fn () => $this->clearCache());

            return $record->refresh();
        });
    }

    public function dateTimeFormat(): string
    {
        $settings = $this->get();

        return $settings->date_format.' '.$settings->time_format;
    }

    public function clearCache(): void
    {
        Cache::forget('business.settings');
    }
}
