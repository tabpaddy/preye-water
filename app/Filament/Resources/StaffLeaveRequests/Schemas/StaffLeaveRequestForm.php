<?php

namespace App\Filament\Resources\StaffLeaveRequests\Schemas;

use App\Filament\Support\WorkforceFields;
use App\Models\LeaveType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class StaffLeaveRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([WorkforceFields::staff()->required(), Select::make('leave_type_id')->options(fn () => LeaveType::where('is_active', true)->pluck('name', 'id'))->required(), DatePicker::make('start_date')->required(), DatePicker::make('end_date')->required()->afterOrEqual('start_date'), Textarea::make('reason')]);
    }
}
