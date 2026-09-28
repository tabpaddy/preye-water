<?php

namespace App\Filament\Resources\WorkShifts\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class WorkShiftsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([TextColumn::make('code')->searchable(), TextColumn::make('name')->searchable(), TextColumn::make('start_time'), TextColumn::make('end_time'), TextColumn::make('is_overnight'), TextColumn::make('break_minutes'), TextColumn::make('is_active')])->filters([TernaryFilter::make('is_active')])->recordActions([ViewAction::make(), EditAction::make(), ...[]])->defaultSort('id', 'desc');
    }
}
