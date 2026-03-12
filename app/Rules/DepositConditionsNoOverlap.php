<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DepositConditionsNoOverlap implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        foreach ($value as $currencyItem) {
            $conditions = $currencyItem['conditions'] ?? [];
            if (! is_array($conditions)) {
                continue;
            }

            $list = array_values($conditions);
            for ($i = 0; $i < count($list); $i++) {
                for ($j = $i + 1; $j < count($list); $j++) {
                    $a = $list[$i];
                    $b = $list[$j];
                    $tMinA = $this->intOrNull($a['term_days_min'] ?? null);
                    $tMaxA = $this->intOrNull($a['term_days_max'] ?? null);
                    $tMinB = $this->intOrNull($b['term_days_min'] ?? null);
                    $tMaxB = $this->intOrNull($b['term_days_max'] ?? null);
                    $amtMinA = $this->floatOrNull($a['amount_min'] ?? null);
                    $amtMaxA = $this->floatOrNull($a['amount_max'] ?? null);
                    $amtMinB = $this->floatOrNull($b['amount_min'] ?? null);
                    $amtMaxB = $this->floatOrNull($b['amount_max'] ?? null);

                    if ($this->rangesOverlap($tMinA, $tMaxA, $tMinB, $tMaxB)
                        && $this->rangesOverlap($amtMinA, $amtMaxA, $amtMinB, $amtMaxB)) {
                        $fail('В рамках одной валюты нельзя допускать пересечения диапазонов срока и суммы у разных условий.');
                        return;
                    }
                }
            }
        }
    }

    private function intOrNull(mixed $v): ?int
    {
        if ($v === null || $v === '') {
            return null;
        }
        return (int) $v;
    }

    private function floatOrNull(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        return (float) $v;
    }

    private function rangesOverlap(?float $min1, ?float $max1, ?float $min2, ?float $max2): bool
    {
        $lo1 = $min1 !== null ? (float) $min1 : 0;
        $hi1 = $max1 !== null ? (float) $max1 : PHP_INT_MAX;
        $lo2 = $min2 !== null ? (float) $min2 : 0;
        $hi2 = $max2 !== null ? (float) $max2 : PHP_INT_MAX;
        if ($min1 === null && $max1 === null) {
            return true;
        }
        if ($min2 === null && $max2 === null) {
            return true;
        }

        return $lo1 <= $hi2 && $lo2 <= $hi1;
    }
}
