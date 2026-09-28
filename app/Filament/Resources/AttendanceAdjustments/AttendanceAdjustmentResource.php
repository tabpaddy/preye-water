<?php

namespace App\Filament\Resources\AttendanceAdjustments;

use App\Models\AttendanceAdjustment;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendanceAdjustmentResource extends Resource
{
    protected static ?string $model = AttendanceAdjustment::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Workforce';

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\AttendanceAdjustmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\AttendanceAdjustmentsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            0 => 'attendance.staff',
            1 => 'requester',
            2 => 'approver',
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAttendanceAdjustments::route('/'), 'view' => Pages\ViewAttendanceAdjustment::route('/{record}')];
    }
}
