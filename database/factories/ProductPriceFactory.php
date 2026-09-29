<?php

namespace Database\Factories;

use App\Enums\PriceType;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductPriceFactory extends Factory
{
    protected $model = ProductPrice::class;

    public function definition(): array
    {
        return ['product_id' => Product::factory(), 'price_type' => PriceType::RETAIL, 'amount' => '1500.00', 'minimum_quantity' => '1.000', 'is_active' => true];
    }
}
