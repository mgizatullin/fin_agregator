<?php

namespace App\Services\DepositConditionsMapper;

use App\Models\DepositCurrency;
use Illuminate\Support\Collection;

/**
 * Строит матрицу ставок для одной валюты: строки = диапазоны сумм, столбцы = сроки, ячейки = ставка.
 */
class DepositRatesMatrix
{
    /**
     * @return array{rows: array<int, array{amount_min: float|null, amount_max: float|null, amount_label: string}>, columns: array<int, int>, grid: array<int, array<int, float|null>>}
     */
    public static function forCurrency(DepositCurrency $currency): array
    {
        $conditions = $currency->conditions->where('is_active', true)->values();
        if ($conditions->isEmpty()) {
            return ['rows' => [], 'columns' => [], 'grid' => []];
        }

        $byAmount = $conditions->groupBy(function ($c) {
            return (string) ($c->amount_min ?? '') . '_' . (string) ($c->amount_max ?? '');
        });
        $rows = $byAmount->sortBy(fn (Collection $g) => (float) ($g->first()->amount_min ?? 0))->values();
        $columns = $conditions->pluck('term_days_min')->unique()->sort()->values()->all();

        $rowList = [];
        $grid = [];
        foreach ($rows as $rowIndex => $rangeConditions) {
            $first = $rangeConditions->first();
            $amountMin = $first->amount_min !== null ? (float) $first->amount_min : null;
            $amountMax = $first->amount_max !== null ? (float) $first->amount_max : null;
            $rowList[] = [
                'amount_min' => $amountMin,
                'amount_max' => $amountMax,
                'amount_label' => self::formatAmountLabel($amountMin, $amountMax),
            ];
            $grid[$rowIndex] = [];
            foreach ($columns as $colIndex => $termDays) {
                $rate = self::findRateForTerm($rangeConditions, $termDays);
                $grid[$rowIndex][$colIndex] = $rate;
            }
        }

        return [
            'rows' => $rowList,
            'columns' => $columns,
            'grid' => $grid,
        ];
    }

    private static function formatAmountLabel(?float $min, ?float $max): string
    {
        if ($min !== null && $max !== null) {
            return number_format($min, 0, '', ' ') . ' – ' . number_format($max, 0, '', ' ');
        }
        if ($min !== null) {
            return 'от ' . number_format($min, 0, '', ' ');
        }
        if ($max !== null) {
            return 'до ' . number_format($max, 0, '', ' ');
        }
        return '—';
    }

    /**
     * @param Collection<int, \App\Models\DepositCondition> $conditions
     */
    private static function findRateForTerm(Collection $conditions, int $termDays): ?float
    {
        foreach ($conditions as $c) {
            $min = (int) $c->term_days_min;
            $max = $c->term_days_max !== null ? (int) $c->term_days_max : null;
            if ($termDays >= $min && ($max === null || $termDays <= $max)) {
                return $c->rate !== null ? (float) $c->rate : null;
            }
        }
        return null;
    }

    public static function formatTermLabel(int $days): string
    {
        $d = $days % 10;
        $d2 = ($days % 100);
        if ($d2 >= 11 && $d2 <= 19) {
            return $days . ' дней';
        }
        return match ($d) {
            1 => $days . ' день',
            2, 3, 4 => $days . ' дня',
            default => $days . ' дней',
        };
    }
}
