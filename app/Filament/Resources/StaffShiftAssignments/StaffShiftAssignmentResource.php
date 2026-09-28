<?php

namespace App\Filament\Resources\StaffShiftAssignments;

use App\Models\StaffShiftAssignment;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StaffShiftAssignmentResource extends Resource
{
    protected static ?string $model = StaffShiftAssignment::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Workforce';

    public static function form(Schema $schema): Schema
    {
        return Schemas\StaffShiftAssignmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\StaffShiftAssignmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\StaffShiftAssignmentsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            0 => 'staff.person',
            1 => 'workShift',
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListStaffShiftAssignments::route('/'), 'view' => Pages\ViewStaffShiftAssignment::route('/{record}'), 'create' => Pages\CreateStaffShiftAssignment::route('/create'), 'edit' => Pages\EditStaffShiftAssignment::route('/{record}/edit')];
    }
}
