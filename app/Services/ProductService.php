<?php

namespace App\Services;

use App\Enums\InventoryItemType;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\Staff;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function __construct(private ActivityLogService $audit, private InventoryItemService $items) {}

    public function create(array $data, Staff $actor): Product
    {
        Gate::forUser($actor)->authorize('create products');

        return DB::transaction(function () use ($data, $actor) {
            if (empty($data['inventory_item_id'])) {
                $item = $this->items->create(array_merge($data['inventory_item'] ?? [], ['item_type' => InventoryItemType::FINISHED_GOOD->value]), $actor);
                $data['inventory_item_id'] = $item->id;
            }

            return $this->save(new Product, $data, $actor);
        });
    }

    public function update(Product $product, array $data, Staff $actor): Product
    {
        Gate::forUser($actor)->authorize('update products');

        return $this->save($product, $data, $actor);
    }

    private function save(Product $product, array $data, Staff $actor): Product
    {
        return DB::transaction(function () use ($product, $data, $actor) {
            $itemId = $product->exists ? $product->inventory_item_id : ($data['inventory_item_id'] ?? null);
            $item = InventoryItem::whereKey($itemId)->lockForUpdate()->firstOrFail();
            if ($product->exists) {
                $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            }
            if (! $item->is_active || $item->item_type !== InventoryItemType::FINISHED_GOOD) {
                throw ValidationException::withMessages(['inventory_item_id' => 'Choose an active finished-good inventory item.']);
            }
            if ($product->exists && isset($data['inventory_item_id']) && (int) $data['inventory_item_id'] !== $item->id) {
                throw ValidationException::withMessages(['inventory_item_id' => 'The linked inventory item cannot be replaced.']);
            }
            $data['inventory_item_id'] = $item->id;
            if (empty($data['slug'])) {
                $base = Str::slug($data['name'] ?? 'product') ?: 'product';
                // The item key is unique and stable; the DB constraint remains authoritative.
                $data['slug'] = $base.'-'.$item->id;
            }
            $data = Validator::make($data, [
                'inventory_item_id' => ['required', Rule::unique('products', 'inventory_item_id')->ignore($product->id)],
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('products', 'slug')->ignore($product->id)],
                'description' => ['nullable', 'string', 'max:10000'],
                'is_featured' => ['sometimes', 'boolean'], 'is_available_online' => ['sometimes', 'boolean'], 'is_active' => ['sometimes', 'boolean'],
            ])->validate();
            $old = $product->toArray();
            $event = $product->exists ? 'updated' : 'created';
            $product->fill($data)->save();
            $this->audit->record('product.'.$event, $product, $actor, 'Product '.$event, $old, $product->toArray());

            return $product;
        });
    }

    public function replaceImage(Product $product, UploadedFile $image, Staff $actor): Product
    {
        Gate::forUser($actor)->authorize('update products');
        Validator::make(['image' => $image], ['image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']])->validate();
        $path = $image->store('products', 'public');
        if ($path === false) {
            throw new \RuntimeException('The product image could not be stored.');
        }
        try {
            return DB::transaction(function () use ($product, $path, $actor) {
                $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
                $old = $product->only('image_path');
                $product->update(['image_path' => $path]);
                $this->audit->record('product.image_updated', $product, $actor, 'Product image updated', $old, ['image_path' => $path]);

                return $product;
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }
    }

    public function archive(Product $product, Staff $actor): void
    {
        Gate::forUser($actor)->authorize('archive products');
        DB::transaction(function () use ($product, $actor) {
            InventoryItem::whereKey($product->inventory_item_id)->lockForUpdate()->firstOrFail();
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $product->delete();
            $this->audit->record('product.archived', $product, $actor, 'Product archived');
        });
    }
}
