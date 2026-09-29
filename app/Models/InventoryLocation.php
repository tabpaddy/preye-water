<?php

namespace App\Models;

use App\Enums\InventoryLocationType;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryLocation extends Model
{
    use HasFactory, HasPublicUuid;

    protected $fillable = ['code', 'name', 'location_type', 'address', 'is_active'];

    protected function casts(): array
    {
        return [
            'location_type' => InventoryLocationType::class,
            'is_active' => 'boolean',
        ];
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function outgoingMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'from_location_id');
    }

    public function incomingMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'to_location_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }
}
