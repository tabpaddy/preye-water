<?php

namespace App\Filament\Resources\Staff\Tables;

use App\Enums\StaffStatus;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StaffTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('staff_number')->searchable()->sortable(),
                TextColumn::make('person.full_name')->label('Name')->searchable(['first_name', 'last_name']),
                TextColumn::make('person.email')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('roles.name')->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->options(array_column(StaffStatus::cases(), 'value', 'value')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
            ]);
    }
}
