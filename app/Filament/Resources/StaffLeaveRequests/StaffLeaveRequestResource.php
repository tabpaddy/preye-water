<?php

namespace App\Filament\Resources\StaffLeaveRequests;

use App\Models\StaffLeaveRequest;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StaffLeaveRequestResource extends Resource
{
    protected static ?string $model = StaffLeaveRequest::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Workforce';

    public static function form(Schema $schema): Schema
    {
        return Schemas\StaffLeaveRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\StaffLeaveRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\StaffLeaveRequestsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            0 => 'staff.person',
            1 => 'leaveType',
            2 => 'approver',
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListStaffLeaveRequests::route('/'), 'view' => Pages\ViewStaffLeaveRequest::route('/{record}'), 'create' => Pages\CreateStaffLeaveRequest::route('/create')];
    }
}
