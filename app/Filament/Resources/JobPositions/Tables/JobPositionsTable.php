<?php

namespace App\Filament\Resources\JobPositions\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class JobPositionsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([TextColumn::make('code')->searchable(), TextColumn::make('name')->searchable(), TextColumn::make('department.name'), TextColumn::make('is_active')])->filters([TernaryFilter::make('is_active'), SelectFilter::make('department_id')->relationship('department', 'name')])->recordActions([ViewAction::make(), EditAction::make(), ...[]])->defaultSort('id', 'desc');
    }
}
