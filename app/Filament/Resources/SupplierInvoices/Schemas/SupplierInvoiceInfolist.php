<?php

namespace App\Filament\Resources\SupplierInvoices\Schemas;

use App\Services\BusinessSettingService;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SupplierInvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('internal_reference'),
            TextEntry::make('invoice_number'),
            TextEntry::make('supplier.name'),
            TextEntry::make('invoice_date')->date(fn () => app(BusinessSettingService::class)->get()->date_format),
            TextEntry::make('due_date')->date(fn () => app(BusinessSettingService::class)->get()->date_format),
            TextEntry::make('total_amount'),
            TextEntry::make('amount_paid'),
            TextEntry::make('amount_due'),
            TextEntry::make('payment_status')->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? str_replace('_', ' ', $state->value) : $state),
            TextEntry::make('notes'),
            RepeatableEntry::make('items')->schema([TextEntry::make('description'), TextEntry::make('quantity'), TextEntry::make('unit_cost'), TextEntry::make('discount_amount'), TextEntry::make('tax_amount'), TextEntry::make('line_total')])->columns(3)->columnSpanFull(),
            TextEntry::make('subtotal'),
            TextEntry::make('discount_amount'),
            TextEntry::make('tax_amount'),
            TextEntry::make('other_amount'),
        ])->columns(3);
    }
}
