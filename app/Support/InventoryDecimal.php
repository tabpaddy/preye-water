<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Validation\ValidationException;

final class InventoryDecimal
{
    public static function value(mixed $value, int $scale = 3, bool $positive = false, string $field = 'quantity'): string
    {
        if ((! is_string($value) && ! is_int($value)) || ! preg_match('/^\d+(\.\d{1,'.$scale.'})?$/', (string) $value)) {
            throw ValidationException::withMessages([$field => "Use a decimal with at most $scale decimal places."]);
        }
        $decimal = BigDecimal::of($value);
        if (($positive && $decimal->isLessThanOrEqualTo(0)) || $decimal->isGreaterThan(BigDecimal::of(10)->power(15 - $scale)->minus('0.'.str_repeat('0', $scale - 1).'1'))) {
            throw ValidationException::withMessages([$field => 'The value is outside the supported range.']);
        }

        return (string) $decimal->toScale($scale);
    }

    public static function cost(string $quantity, ?string $unitCost): ?string
    {
        return $unitCost === null ? null : self::value((string) BigDecimal::of($quantity)->multipliedBy($unitCost)->toScale(2, RoundingMode::HALF_UP), 2, false, 'total_cost');
    }
}
