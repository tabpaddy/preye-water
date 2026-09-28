<?php

namespace App\Filament\Resources\LeaveTypes\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class LeaveTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([TextColumn::make('code')->searchable(), TextColumn::make('name')->searchable(), TextColumn::make('is_paid'), TextColumn::make('requires_approval'), TextColumn::make('is_active')])->filters([TernaryFilter::make('is_active')])->recordActions([ViewAction::make(), EditAction::make(), ...[]])->defaultSort('id', 'desc');
    }
}
