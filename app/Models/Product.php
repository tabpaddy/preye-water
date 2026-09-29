<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, HasPublicUuid, SoftDeletes;

    protected $fillable = ['inventory_item_id', 'name', 'slug', 'description', 'image_path', 'is_featured', 'is_available_online', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_available_online' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class)->withTrashed();
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereHas('inventoryItem', fn ($q) => $q->whereNull('deleted_at')->where('is_active', true));
    }

    public function scopeAvailableOnline($query)
    {
        return $query->active()->where('is_available_online', true);
    }

    public function scopeFeatured($query)
    {
        return $query->active()->where('is_featured', true);
    }
}
