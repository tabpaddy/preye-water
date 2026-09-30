<?php

namespace App\Services;

use App\Enums\SupplierStatus;
use App\Enums\SupplierType;
use App\Models\Staff;
use App\Models\Supplier;
use App\Models\SupplierAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupplierService
{
    public function __construct(private NumberSequenceService $numbers, private ActivityLogService $audit) {}

    public function create(array $data, Staff $actor): Supplier
    {
        Gate::forUser($actor)->authorize('create suppliers');

        return $this->save(new Supplier, $data, $actor);
    }

    public function update(Supplier $supplier, array $data, Staff $actor): Supplier
    {
        Gate::forUser($actor)->authorize('update suppliers');

        return $this->save($supplier, $data, $actor);
    }

    private function save(Supplier $supplier, array $data, Staff $actor): Supplier
    {
        return DB::transaction(function () use ($supplier, $data, $actor) {
            if ($supplier->exists) {
                $supplier = Supplier::whereKey($supplier->id)->lockForUpdate()->firstOrFail();
            }
            $rules = ['name' => ['required', 'string', 'max:255'], 'supplier_type' => ['nullable', Rule::enum(SupplierType::class)],
                'email' => ['nullable', 'email', 'max:255'], 'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
                'status' => ['required', Rule::enum(SupplierStatus::class)], 'notes' => ['nullable', 'string', 'max:5000']];
            foreach (['contact_person', 'phone', 'alternate_phone', 'tax_identification_number'] as $field) {
                $rules[$field] = ['nullable', 'string', 'max:255'];
            }
            $data = Validator::make($data, $rules)->validate();
            $old = $supplier->only(array_keys($rules));
            $event = $supplier->exists ? 'updated' : 'created';
            if (! $supplier->exists) {
                $supplier->supplier_number = $this->numbers->next('SUPPLIER');
            }
            $supplier->fill($data)->save();
            $this->audit->record('supplier.'.$event, $supplier, $actor, 'Supplier '.$event, $old, $supplier->only(array_keys($rules)));

            return $supplier;
        }, 5);
    }

    public function archive(Supplier $supplier, Staff $actor): void
    {
        Gate::forUser($actor)->authorize('archive suppliers');
        DB::transaction(function () use ($supplier, $actor) {
            $supplier = Supplier::whereKey($supplier->id)->lockForUpdate()->firstOrFail();
            $supplier->delete();
            $this->audit->record('supplier.archived', $supplier, $actor, 'Supplier archived');
        });
    }

    public function saveAddress(Supplier $supplier, array $data, Staff $actor, ?SupplierAddress $address = null): SupplierAddress
    {
        Gate::forUser($actor)->authorize('update suppliers');

        return DB::transaction(function () use ($supplier, $data, $actor, $address) {
            $supplier = Supplier::whereKey($supplier->id)->lockForUpdate()->firstOrFail();
            if ($address) {
                $address = SupplierAddress::whereKey($address->id)->lockForUpdate()->firstOrFail();
                if ($address->supplier_id !== $supplier->id) {
                    throw ValidationException::withMessages(['supplier_id' => 'Address belongs to another supplier.']);
                }
            } else {
                $address = new SupplierAddress(['supplier_id' => $supplier->id]);
            }
            $rules = [];
            foreach (['label', 'address_line_1', 'address_line_2', 'landmark', 'city', 'lga', 'state', 'country', 'postal_code'] as $field) {
                $rules[$field] = [in_array($field, ['address_line_1', 'city', 'state', 'country']) ? 'required' : 'nullable', 'string', 'max:255'];
            }
            $rules += ['is_default' => ['required', 'boolean'], 'is_active' => ['required', 'boolean']];
            $data = Validator::make($data, $rules)->validate();
            if ($data['is_default'] && ! $data['is_active']) {
                throw ValidationException::withMessages(['is_default' => 'A default address must be active.']);
            }
            if ($data['is_default']) {
                $supplier->addresses()->where('is_default', true)->update(['is_default' => false]);
            }
            $old = $address->only(array_keys($rules));
            $address->fill($data)->save();
            $this->audit->record('supplier.address_saved', $address, $actor, 'Supplier address saved', $old, $data);

            return $address;
        });
    }

    /** Shared lock order: supplier, procurement document, child rows, inventory. */
    public function lockActive(int $id): Supplier
    {
        $supplier = Supplier::withTrashed()->whereKey($id)->lockForUpdate()->firstOrFail();
        if ($supplier->trashed() || $supplier->status !== SupplierStatus::ACTIVE) {
            throw ValidationException::withMessages(['supplier_id' => 'Supplier must be active.']);
        }

        return $supplier;
    }
}
