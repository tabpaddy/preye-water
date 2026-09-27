<?php

namespace App\Filament\Resources\SystemSettings\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SystemSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('group')->searchable(),
                TextColumn::make('key')->searchable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('value')->limit(80),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
            ]);
    }
}
