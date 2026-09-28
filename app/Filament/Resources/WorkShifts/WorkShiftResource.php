<?php

namespace App\Filament\Resources\WorkShifts;

use App\Models\WorkShift;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WorkShiftResource extends Resource
{
    protected static ?string $model = WorkShift::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Workforce';

    public static function form(Schema $schema): Schema
    {
        return Schemas\WorkShiftForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\WorkShiftInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\WorkShiftsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListWorkShifts::route('/'), 'view' => Pages\ViewWorkShift::route('/{record}'), 'create' => Pages\CreateWorkShift::route('/create'), 'edit' => Pages\EditWorkShift::route('/{record}/edit')];
    }
}
