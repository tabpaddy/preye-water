<?php

namespace App\Services;

use App\Enums\ResetFrequency;
use App\Models\NumberSequence;
use Illuminate\Support\Facades\DB;

class NumberSequenceService
{
    public function next(string $key): string
    {
        return DB::transaction(function () use ($key) {
            $sequence = NumberSequence::where('key', $key)->lockForUpdate()->firstOrFail();
            $now = now('Africa/Lagos');
            $period = match ($sequence->reset_frequency) {
                ResetFrequency::YEARLY => 'Y',
                ResetFrequency::MONTHLY => 'Y-m',
                ResetFrequency::NEVER => null,
            };
            if ($period && (! $sequence->last_reset_at || $sequence->last_reset_at->timezone('Africa/Lagos')->format($period) !== $now->format($period))) {
                $sequence->current_number = 0;
                $sequence->last_reset_at = $now->copy()->utc();
            }
            $sequence->current_number++;
            $sequence->save();

            return implode('-', array_filter([$sequence->prefix, $period ? $now->format($period) : null, str_pad((string) $sequence->current_number, $sequence->padding, '0', STR_PAD_LEFT)]));
        }, 5);
    }
}
