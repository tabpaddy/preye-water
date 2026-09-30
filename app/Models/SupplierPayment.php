<?php

namespace App\Models;

use App\Enums\SupplierPaymentMethod;
use App\Enums\SupplierPaymentStatus;
use App\Models\Concerns\HasPublicUuid;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierPayment extends Model
{
    use HasFactory,HasPublicUuid;

    protected $fillable = ['payment_number', 'supplier_id', 'payment_method', 'amount', 'currency', 'reference', 'status', 'paid_at', 'recorded_by', 'approved_by', 'notes'];

    protected function casts(): array
    {
        return ['payment_method' => SupplierPaymentMethod::class, 'status' => SupplierPaymentStatus::class, 'paid_at' => 'immutable_datetime', 'amount' => 'decimal:2'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SupplierPaymentAllocation::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'recorded_by')->withTrashed();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by')->withTrashed();
    }

    public function getAllocatedAmountAttribute(): string
    {
        $total = BigDecimal::zero();
        foreach ($this->allocations as $allocation) {
            $total = $total->plus($allocation->amount);
        }

        return (string) $total->toScale(2);
    }

    public function getUnallocatedAmountAttribute(): string
    {
        return (string) BigDecimal::of($this->amount)->minus($this->allocated_amount)->toScale(2);
    }

    protected static function booted(): void
    {
        static::updating(function (self $record): void {
            if ($record->getRawOriginal('status') === SupplierPaymentStatus::COMPLETED->value) {
                throw new \LogicException('Completed supplier payments are immutable.');
            }
        });
        static::deleting(function (self $record): void {
            if ($record->status === SupplierPaymentStatus::COMPLETED) {
                throw new \LogicException('Completed supplier payments cannot be deleted.');
            }
        });
    }
}
