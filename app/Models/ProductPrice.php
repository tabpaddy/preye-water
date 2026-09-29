<?php

namespace App\Models;

use App\Enums\PriceType;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    use HasFactory, HasPublicUuid;

    protected $fillable = ['product_id', 'price_type', 'amount', 'minimum_quantity', 'effective_from', 'effective_until', 'is_active'];

    protected function casts(): array
    {
        return [
            'price_type' => PriceType::class,
            'amount' => 'decimal:2',
            'minimum_quantity' => 'decimal:3',
            'effective_from' => 'immutable_datetime',
            'effective_until' => 'immutable_datetime',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
