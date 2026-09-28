<?php

namespace App\Filament\Support;

use App\Models\Staff;
use Filament\Forms\Components\Select;

class WorkforceFields
{
    public static function staff(string $field = 'staff_id'): Select
    {
        return Select::make($field)->label($field === 'manager_id' ? 'Manager' : 'Staff')->searchable()
            ->getSearchResultsUsing(fn (string $search) => Staff::with('person')->where(fn ($q) => $q->where('staff_number', 'like', "%{$search}%")->orWhereHas('person', fn ($p) => $p->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")))->limit(50)->get()->mapWithKeys(fn (Staff $s) => [$s->id => $s->staff_number.' - '.$s->getFilamentName()])->all())
            ->getOptionLabelUsing(fn ($value) => ($s = Staff::withTrashed()->with('person')->find($value)) ? $s->staff_number.' - '.$s->getFilamentName() : null);
    }
}
