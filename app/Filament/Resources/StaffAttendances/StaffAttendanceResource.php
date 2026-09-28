<?php

namespace App\Filament\Resources\StaffAttendances;

use App\Models\StaffAttendance;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StaffAttendanceResource extends Resource
{
    protected static ?string $model = StaffAttendance::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Workforce';

    public static function form(Schema $schema): Schema
    {
        return Schemas\StaffAttendanceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\StaffAttendanceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\StaffAttendancesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            0 => 'staff.person',
            1 => 'staff.employmentDetail.department',
            2 => 'workShift',
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListStaffAttendances::route('/'), 'view' => Pages\ViewStaffAttendance::route('/{record}'), 'create' => Pages\CreateStaffAttendance::route('/create')];
    }
}
