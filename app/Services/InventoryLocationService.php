<?php

namespace App\Services;

use App\Enums\InventoryLocationType;
use App\Models\InventoryLocation;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class InventoryLocationService
{
    public function __construct(private ActivityLogService $audit) {}

    public function create(array $data, Staff $actor): InventoryLocation
    {
        Gate::forUser($actor)->authorize('create inventory locations');

        return $this->save(new InventoryLocation, $data, $actor);
    }

    public function update(InventoryLocation $location, array $data, Staff $actor): InventoryLocation
    {
        Gate::forUser($actor)->authorize('update inventory locations');

        return $this->save($location, $data, $actor);
    }

    private function save(InventoryLocation $location, array $data, Staff $actor): InventoryLocation
    {
        return DB::transaction(function () use ($location, $data, $actor) {
            if ($location->exists) {
                $location = InventoryLocation::whereKey($location->id)->lockForUpdate()->firstOrFail();
            }
            $data = Validator::make($data, [
                'code' => ['required', 'string', 'max:255', Rule::unique('inventory_locations', 'code')->ignore($location->id)],
                'name' => ['required', 'string', 'max:255'], 'location_type' => ['required', Rule::enum(InventoryLocationType::class)],
                'address' => ['nullable', 'string', 'max:5000'], 'is_active' => ['sometimes', 'boolean'],
            ])->validate();
            $old = $location->toArray();
            $event = $location->exists ? 'updated' : 'created';
            $location->fill($data)->save();
            $this->audit->record('inventory_location.'.$event, $location, $actor, 'Inventory location '.$event, $old, $location->toArray());

            return $location;
        });
    }
}
