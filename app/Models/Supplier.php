<?php

namespace App\Models;

use App\Enums\SupplierStatus;
use App\Enums\SupplierType;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory,HasPublicUuid,SoftDeletes;

    protected $fillable = ['supplier_number', 'name', 'supplier_type', 'contact_person', 'phone', 'alternate_phone', 'email', 'tax_identification_number', 'payment_terms_days', 'status', 'notes'];

    protected function casts(): array
    {
        return ['supplier_type' => SupplierType::class, 'status' => SupplierStatus::class, 'payment_terms_days' => 'integer'];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(SupplierAddress::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SupplierInvoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }
}
