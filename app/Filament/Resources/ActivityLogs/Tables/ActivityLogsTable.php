<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use App\Services\BusinessSettingService;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('event')->searchable(),
                TextColumn::make('staff.staff_number')->label('Actor'),
                TextColumn::make('description')->wrap(),
                TextColumn::make('subject_type')->label('Subject type'),
                TextColumn::make('subject_id'),
                TextColumn::make('created_at')->dateTime(fn () => app(BusinessSettingService::class)->dateTimeFormat(), fn () => app(BusinessSettingService::class)->get()->timezone)->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
            ]);
    }
}
