<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Illuminate\Validation\ValidationException;

final class ProcurementTotals
{
    public static function line(array $data, string $quantityField): array
    {
        $quantity = InventoryDecimal::value($data[$quantityField] ?? null, 3, true, $quantityField);
        $cost = InventoryDecimal::value($data['unit_cost'] ?? null, 4, false, 'unit_cost');
        $discount = InventoryDecimal::value($data['discount_amount'] ?? '0', 2, false, 'discount_amount');
        $tax = InventoryDecimal::value($data['tax_amount'] ?? '0', 2, false, 'tax_amount');
        $gross = BigDecimal::of(InventoryDecimal::cost($quantity, $cost));
        if ($gross->isLessThan($discount)) {
            throw ValidationException::withMessages(['discount_amount' => 'Line discount exceeds its gross amount.']);
        }

        return [$quantityField => $quantity, 'unit_cost' => $cost, 'discount_amount' => $discount, 'tax_amount' => $tax,
            'line_total' => InventoryDecimal::value((string) $gross->minus($discount)->plus($tax), 2, false, 'line_total')];
    }

    public static function document(array $lines, array $data, string $otherField, bool $positive = false): array
    {
        $subtotal = BigDecimal::zero();
        foreach ($lines as $line) {
            $subtotal = $subtotal->plus($line['line_total']);
        }
        $discount = InventoryDecimal::value($data['discount_amount'] ?? '0', 2, false, 'discount_amount');
        $tax = InventoryDecimal::value($data['tax_amount'] ?? '0', 2, false, 'tax_amount');
        $other = InventoryDecimal::value($data[$otherField] ?? '0', 2, false, $otherField);
        if ($subtotal->isLessThan($discount)) {
            throw ValidationException::withMessages(['discount_amount' => 'Document discount exceeds the subtotal.']);
        }

        return ['subtotal' => InventoryDecimal::value((string) $subtotal, 2, false, 'subtotal'),
            'discount_amount' => $discount, 'tax_amount' => $tax, $otherField => $other,
            'total_amount' => InventoryDecimal::value((string) $subtotal->minus($discount)->plus($tax)->plus($other),2,$positive,'total_amount')];
    }
}
