<?php

namespace App\Filament\Resources\AttendanceAdjustments\Tables;

use App\Filament\Support\WorkforceActions;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendanceAdjustmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([TextColumn::make('attendance.attendance_date'), TextColumn::make('attendance.staff.staff_number'), TextColumn::make('adjustment_type')->badge(), TextColumn::make('old_value'), TextColumn::make('new_value'), TextColumn::make('reason'), TextColumn::make('status')->badge(), TextColumn::make('requester.staff_number'), TextColumn::make('approver.staff_number')])->filters([])->recordActions([ViewAction::make(), ...WorkforceActions::adjustments()])->defaultSort('id', 'desc');
    }
}
