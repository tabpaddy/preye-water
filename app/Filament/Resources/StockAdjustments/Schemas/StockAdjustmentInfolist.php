<?php

namespace App\Filament\Resources\StockAdjustments\Schemas;

use App\Services\BusinessSettingService;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class StockAdjustmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('adjustment_number'),
            TextEntry::make('inventoryLocation.name'),
            TextEntry::make('reason')->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? str_replace('_', ' ', $state->value) : $state),
            TextEntry::make('status')->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? str_replace('_', ' ', $state->value) : $state),
            TextEntry::make('creator.staff_number'),
            TextEntry::make('approver.staff_number'),
            TextEntry::make('posted_at')->dateTime()->timezone(fn () => app(BusinessSettingService::class)->get()->timezone),
            RepeatableEntry::make('items')->schema([
                TextEntry::make('inventoryItem.name'), TextEntry::make('system_quantity'),
                TextEntry::make('counted_quantity'), TextEntry::make('difference_quantity'), TextEntry::make('notes'),
            ])->columns(5)->columnSpanFull(),
            TextEntry::make('notes'),
        ])->columns(3);
    }
}
