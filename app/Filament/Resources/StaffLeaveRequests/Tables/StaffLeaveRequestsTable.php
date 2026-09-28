<?php

namespace App\Filament\Resources\StaffLeaveRequests\Tables;

use App\Filament\Support\WorkforceActions;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StaffLeaveRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([TextColumn::make('request_number')->searchable(), TextColumn::make('staff.staff_number')->searchable(), TextColumn::make('staff.person.full_name'), TextColumn::make('leaveType.name'), TextColumn::make('start_date'), TextColumn::make('end_date'), TextColumn::make('total_days'), TextColumn::make('reason'), TextColumn::make('status')->badge(), TextColumn::make('approver.staff_number')])->filters([])->recordActions([ViewAction::make(), ...WorkforceActions::leave()])->defaultSort('id', 'desc');
    }
}
