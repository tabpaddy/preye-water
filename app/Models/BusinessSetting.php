<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessSetting extends Model
{
    protected $fillable = ['business_id', 'currency', 'timezone', 'date_format', 'time_format', 'low_stock_notification', 'allow_customer_online_orders', 'allow_cash_payments', 'allow_bank_transfer', 'allow_pos_payments', 'online_payment_enabled', 'default_payment_provider', 'invoice_prefix', 'receipt_prefix', 'order_prefix', 'sale_prefix'];

    protected function casts(): array
    {
        return array_fill_keys(['low_stock_notification', 'allow_customer_online_orders', 'allow_cash_payments', 'allow_bank_transfer', 'allow_pos_payments', 'online_payment_enabled'], 'boolean');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
