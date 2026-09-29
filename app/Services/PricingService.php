<?php

namespace App\Services;

use App\Enums\PriceType;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Staff;
use App\Support\InventoryDecimal;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PricingService
{
    public function __construct(private ActivityLogService $audit) {}

    public function create(Product $product, array $data, Staff $actor): ProductPrice
    {
        return $this->save($product, new ProductPrice, $data, $actor);
    }

    public function update(ProductPrice $price, array $data, Staff $actor): ProductPrice
    {
        return $this->save($price->product, $price, $data, $actor);
    }

    private function save(Product $product, ProductPrice $price, array $data, Staff $actor): ProductPrice
    {
        Gate::forUser($actor)->authorize('manage product prices');

        return DB::transaction(function () use ($product, $price, $data, $actor) {
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            if ($price->exists) {
                $price = ProductPrice::whereKey($price->id)->lockForUpdate()->firstOrFail();
            }
            $data = Validator::make($data, [
                'price_type' => ['required', Rule::enum(PriceType::class)], 'amount' => ['required'],
                'minimum_quantity' => ['required'], 'effective_from' => ['nullable', 'date'],
                'effective_until' => ['nullable', 'date'], 'is_active' => ['required', 'boolean'],
            ])->validate();
            $data['amount'] = InventoryDecimal::value($data['amount'], 2, true, 'amount');
            $data['minimum_quantity'] = InventoryDecimal::value($data['minimum_quantity'], 3, true, 'minimum_quantity');
            foreach (['effective_from', 'effective_until'] as $field) {
                $data[$field] = empty($data[$field]) ? null : CarbonImmutable::parse($data[$field])->utc();
            }
            if ($data['effective_from'] && $data['effective_until'] && $data['effective_until']->lt($data['effective_from'])) {
                throw ValidationException::withMessages(['effective_until' => 'End must be on or after start.']);
            }
            $overlap = ProductPrice::where('product_id', $product->id)->where('price_type', $data['price_type'])->where('minimum_quantity', $data['minimum_quantity'])->where('is_active', true)
                ->when($price->exists, fn ($q) => $q->whereKeyNot($price->id))
                ->when($data['effective_until'], fn ($q) => $q->where(fn ($q) => $q->whereNull('effective_from')->orWhere('effective_from', '<=', $data['effective_until'])))
                ->when($data['effective_from'], fn ($q) => $q->where(fn ($q) => $q->whereNull('effective_until')->orWhere('effective_until', '>=', $data['effective_from'])))->exists();
            if ($data['is_active'] && $overlap) {
                throw ValidationException::withMessages(['minimum_quantity' => 'An active price for this type and quantity tier overlaps this window.']);
            }
            $old = $price->toArray();
            $event = $price->exists ? 'updated' : 'created';
            $price->fill($data + ['product_id' => $product->id])->save();
            $this->audit->record('product_price.'.$event, $price, $actor, 'Product price '.$event, $old, $price->toArray());

            return $price;
        });
    }

    public function resolve(Product $product, PriceType $priceType, mixed $quantity, ?DateTimeInterface $at = null): ProductPrice
    {
        $quantity = InventoryDecimal::value($quantity, 3, true);
        $product = Product::active()->whereKey($product->id)->first();
        if (! $product) {
            throw ValidationException::withMessages(['product' => 'Product is unavailable.']);
        }
        $at = $at ? CarbonImmutable::instance($at)->utc() : CarbonImmutable::now('UTC');
        $price = $product->prices()->where('price_type', $priceType)->where('is_active', true)->where('minimum_quantity', '<=', $quantity)
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhere('effective_from', '<=', $at))
            ->where(fn ($q) => $q->whereNull('effective_until')->orWhere('effective_until', '>=', $at))
            ->orderByDesc('minimum_quantity')->orderByDesc('effective_from')->orderByDesc('id')->first();
        if (! $price) {
            throw ValidationException::withMessages(['price' => 'No applicable price is configured.']);
        }

        return $price;
    }
}
