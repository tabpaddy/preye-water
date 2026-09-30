<?php

namespace App\Filament\Resources\SupplierPayments\Schemas;

use App\Services\BusinessSettingService;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SupplierPaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('payment_number'),
            TextEntry::make('supplier.name'),
            TextEntry::make('payment_method')->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? str_replace('_', ' ', $state->value) : $state),
            TextEntry::make('amount'),
            TextEntry::make('status')->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? str_replace('_', ' ', $state->value) : $state),
            TextEntry::make('paid_at')->dateTime(fn () => app(BusinessSettingService::class)->dateTimeFormat())->timezone(fn () => app(BusinessSettingService::class)->get()->timezone),
            TextEntry::make('recorder.staff_number'),
            TextEntry::make('approver.staff_number'),
            TextEntry::make('notes'),
            RepeatableEntry::make('allocations')->schema([TextEntry::make('supplierInvoice.invoice_number'), TextEntry::make('supplierInvoice.total_amount'), TextEntry::make('supplierInvoice.amount_paid'), TextEntry::make('supplierInvoice.amount_due'), TextEntry::make('amount')])->columns(3)->columnSpanFull(),
            TextEntry::make('allocated_amount'),
            TextEntry::make('unallocated_amount'),
            TextEntry::make('reference'),
            TextEntry::make('currency'),
        ])->columns(3);
    }
}
