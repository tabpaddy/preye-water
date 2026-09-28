<?php

namespace App\Services;

use App\Models\StaffAttendance;
use App\Models\WorkShift;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class AttendanceTimeService
{
    public function __construct(private BusinessSettingService $settings) {}

    public function timezone(): string
    {
        return $this->settings->get()->timezone;
    }

    public function local(string $value): CarbonImmutable
    {
        return CarbonImmutable::parse($value, $this->timezone());
    }

    public function snapshot(?WorkShift $shift): ?array
    {
        return $shift?->only(['start_time', 'end_time', 'grace_period_minutes', 'break_minutes', 'is_overnight']);
    }

    public function calculate(StaffAttendance $attendance): array
    {
        $in = $attendance->clock_in_at;
        $out = $attendance->clock_out_at;
        if ($out && (! $in || $out->lessThan($in))) {
            throw ValidationException::withMessages(['clock_out_at' => 'Clock out must follow clock in.']);
        }
        $minutes = $in && $out ? (int) floor($in->diffInMinutes($out)) : null;
        $metrics = ['late_minutes' => 0, 'early_departure_minutes' => 0, 'worked_minutes' => $minutes, 'overtime_minutes' => 0];
        if (! $attendance->shift_snapshot) {
            return $metrics;
        }
        $shift = $attendance->shift_snapshot;
        $zone = $shift['timezone'] ?? $this->timezone();
        $date = $attendance->attendance_date->toDateString();
        $start = CarbonImmutable::parse($date.' '.$shift['start_time'], $zone);
        $end = CarbonImmutable::parse($date.' '.$shift['end_time'], $zone);
        if ($shift['is_overnight']) {
            $end = $end->addDay();
        }
        if ($in) {
            $metrics['late_minutes'] = max(0, (int) floor($start->addMinutes($shift['grace_period_minutes'])->diffInMinutes($in, false)));
        }
        if ($out) {
            $metrics['early_departure_minutes'] = max(0, (int) floor($out->diffInMinutes($end, false)));
            $metrics['worked_minutes'] = max(0, $minutes - $shift['break_minutes']);
            $metrics['overtime_minutes'] = max(0, (int) floor($end->diffInMinutes($out, false)));
        }

        return $metrics;
    }
}
