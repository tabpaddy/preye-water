<?php

namespace App\Filament\Resources\Departments;

use App\Models\Department;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Workforce';

    public static function form(Schema $schema): Schema
    {
        return Schemas\DepartmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\DepartmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\DepartmentsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            0 => 'manager.person',
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListDepartments::route('/'), 'view' => Pages\ViewDepartment::route('/{record}'), 'create' => Pages\CreateDepartment::route('/create'), 'edit' => Pages\EditDepartment::route('/{record}/edit')];
    }
}
