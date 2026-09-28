<?php

namespace App\Filament\Resources\LeaveTypes;

use App\Models\LeaveType;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeaveTypeResource extends Resource
{
    protected static ?string $model = LeaveType::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Workforce';

    public static function form(Schema $schema): Schema
    {
        return Schemas\LeaveTypeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\LeaveTypeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\LeaveTypesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListLeaveTypes::route('/'), 'view' => Pages\ViewLeaveType::route('/{record}'), 'create' => Pages\CreateLeaveType::route('/create'), 'edit' => Pages\EditLeaveType::route('/{record}/edit')];
    }
}
