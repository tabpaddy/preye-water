<?php

namespace App\Filament\Resources\Staff\Schemas;

use App\Models\StaffEmploymentDetail;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;

class StaffEmploymentInfolist
{
    public static function components(): array
    {
        if (! auth('staff')->user()->can('view staff employment details')) {
            return [];
        }
        $fields = [];
        foreach (['department.name', 'jobPosition.name', 'employment_type', 'employment_date', 'confirmation_date', 'termination_date', 'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship'] as $field) {
            $entry = TextEntry::make('employmentDetail.'.$field);
            if (str_ends_with($field, '_date')) {
                $entry->date();
            }
            $fields[] = $entry;
        }
        if (auth('staff')->user()->can('view staff salaries')) {
            foreach (StaffEmploymentDetail::SENSITIVE as $field) {
                $fields[] = TextEntry::make('employmentDetail.'.$field);
            }
        }

        return [Section::make('Employment')->schema($fields)->columns(2)];
    }
}
