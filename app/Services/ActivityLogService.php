<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    public function record(string $event, Model $subject, ?Staff $actor, string $description, array $oldValues = [], array $newValues = [], array $metadata = []): ActivityLog
    {
        return ActivityLog::create([
            'staff_id' => $actor?->id,
            'event' => $event,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'description' => $description,
            'old_values' => $this->sanitize($oldValues),
            'new_values' => $this->sanitize($newValues),
            'metadata' => $this->sanitize($metadata),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    private function sanitize(array $values): array
    {
        foreach ($values as $key => $value) {
            if (preg_match('/password|token|secret|api.?key|authorization/i', (string) $key)) {
                unset($values[$key]);
            } elseif (is_array($value)) {
                $values[$key] = $this->sanitize($value);
            }
        }

        return $values;
    }
}
