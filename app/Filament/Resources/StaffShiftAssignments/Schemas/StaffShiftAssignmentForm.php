<?php

namespace App\Filament\Resources\StaffShiftAssignments\Schemas;

use App\Filament\Support\WorkforceFields;
use App\Models\WorkShift;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class StaffShiftAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([WorkforceFields::staff()->required()->disabledOn('edit')->dehydrated(), Select::make('work_shift_id')->options(fn () => WorkShift::pluck('name', 'id'))->required()->searchable(), DatePicker::make('effective_from')->required(), DatePicker::make('effective_until')->afterOrEqual('effective_from'), Textarea::make('notes')]);
    }
}
