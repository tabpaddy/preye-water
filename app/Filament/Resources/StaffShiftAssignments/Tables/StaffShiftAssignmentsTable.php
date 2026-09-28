<?php

namespace App\Filament\Resources\StaffShiftAssignments\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StaffShiftAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([TextColumn::make('staff.staff_number')->searchable(), TextColumn::make('staff.person.full_name'), TextColumn::make('workShift.name'), TextColumn::make('effective_from'), TextColumn::make('effective_until'), TextColumn::make('period')->getStateUsing(fn ($record) => $record->effective_from->isFuture() ? 'Future' : ($record->effective_until && $record->effective_until->lt(today()) ? 'Expired' : 'Current'))])->filters([])->recordActions([ViewAction::make(), EditAction::make(), ...[]])->defaultSort('id', 'desc');
    }
}
