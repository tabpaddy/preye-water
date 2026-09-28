<?php

namespace App\Filament\Resources\JobPositions;

use App\Models\JobPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class JobPositionResource extends Resource
{
    protected static ?string $model = JobPosition::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Workforce';

    public static function form(Schema $schema): Schema
    {
        return Schemas\JobPositionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\JobPositionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\JobPositionsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            0 => 'department',
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListJobPositions::route('/'), 'view' => Pages\ViewJobPosition::route('/{record}'), 'create' => Pages\CreateJobPosition::route('/create'), 'edit' => Pages\EditJobPosition::route('/{record}/edit')];
    }
}
