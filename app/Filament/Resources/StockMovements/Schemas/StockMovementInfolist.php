<?php

namespace App\Filament\Resources\StockMovements\Schemas;

use App\Services\BusinessSettingService;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class StockMovementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('uuid'),
            TextEntry::make('inventoryItem.name'),
            TextEntry::make('movement_type')->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? str_replace('_', ' ', $state->value) : $state),
            TextEntry::make('fromLocation.name'),
            TextEntry::make('toLocation.name'),
            TextEntry::make('quantity'),
            TextEntry::make('unit_cost'),
            TextEntry::make('total_cost'),
            TextEntry::make('reference_number'),
            TextEntry::make('performer.staff_number'),
            TextEntry::make('occurred_at')->dateTime()->timezone(fn () => app(BusinessSettingService::class)->get()->timezone),
            TextEntry::make('notes'),
        ])->columns(3);
    }
}
