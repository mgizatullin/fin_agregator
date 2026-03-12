<?php

namespace App\Services\DepositConditionsMapper;

use App\Models\Deposit;
use App\Models\DepositCondition;

/**
 * Преобразование между вложенной структурой формы (валюта → диапазон сумм → срок→ставка)
 * и плоскими таблицами deposit_currencies + deposit_conditions.
 */
class DepositConditionsMapper
{
    /**
     * Построить данные для формы из вклада (currencies → amount_ranges → terms).
     *
     * @return array<int, array{currency_code: string, amount_ranges: array<int, array{amount_min: mixed, amount_max: mixed, terms: array<int, array{term_days: mixed, rate: mixed, is_active: bool}>}>}>
     */
    public static function toFormStructure(Deposit $deposit): array
    {
        $deposit->loadMissing(['currencies.conditions']);

        $result = [];
        foreach ($deposit->currencies as $currency) {
            $conditions = $currency->conditions->sortBy('amount_min')->sortBy('term_days_min')->values();

            $groupedByAmount = $conditions->groupBy(function (DepositCondition $c) {
                return (string) ($c->amount_min ?? '') . '_' . (string) ($c->amount_max ?? '');
            });

            $amountRanges = [];
            foreach ($groupedByAmount as $group) {
                $first = $group->first();
                $terms = $group->sortBy('term_days_min')->values()->map(function (DepositCondition $c) {
                    return [
                        'term_days' => $c->term_days_min ?? $c->term_days_max,
                        'rate' => $c->rate,
                        'is_active' => (bool) $c->is_active,
                    ];
                })->values()->all();

                $amountRanges[] = [
                    'amount_min' => $first->amount_min,
                    'amount_max' => $first->amount_max,
                    'terms' => $terms,
                ];
            }

            usort($amountRanges, function ($a, $b) {
                $minA = (float) ($a['amount_min'] ?? 0);
                $minB = (float) ($b['amount_min'] ?? 0);
                return $minA <=> $minB;
            });

            $result[] = [
                'currency_code' => $currency->currency_code,
                'amount_ranges' => $amountRanges,
            ];
        }

        return $result;
    }

    /**
     * Сохранить вложенные данные формы в deposit_currencies и deposit_conditions.
     * term_days_max = следующий срок - 1, для последнего — null.
     */
    public static function fromFormStructure(Deposit $deposit, array $currencies): void
    {
        $deposit->currencies()->delete();

        foreach ($currencies as $currencyIndex => $currencyData) {
            $currencyCode = $currencyData['currency_code'] ?? null;
            if ($currencyCode === null || $currencyCode === '') {
                continue;
            }

            $currency = $deposit->currencies()->create([
                'currency_code' => $currencyCode,
                'min_amount' => null,
                'max_amount' => null,
                'sort_order' => $currencyIndex,
            ]);

            $amountRanges = $currencyData['amount_ranges'] ?? [];
            $sortOrder = 0;

            foreach ($amountRanges as $range) {
                $amountMin = isset($range['amount_min']) && $range['amount_min'] !== '' ? (float) $range['amount_min'] : null;
                $amountMax = isset($range['amount_max']) && $range['amount_max'] !== '' ? (float) $range['amount_max'] : null;

                $terms = $range['terms'] ?? [];
                $terms = collect($terms)->filter(function ($t) {
                    $td = $t['term_days'] ?? null;
                    return $td !== '' && $td !== null;
                })->sortBy('term_days')->values()->all();

                foreach ($terms as $termIndex => $term) {
                    $termDays = isset($term['term_days']) && $term['term_days'] !== '' && $term['term_days'] !== null
                        ? (int) $term['term_days']
                        : null;
                    if ($termDays === null) {
                        continue;
                    }

                    $next = $terms[$termIndex + 1] ?? null;
                    $nextTermDays = $next !== null && isset($next['term_days']) && $next['term_days'] !== ''
                        ? (int) $next['term_days']
                        : null;
                    $termDaysMax = $nextTermDays !== null ? $nextTermDays - 1 : null;

                    $currency->conditions()->create([
                        'term_days_min' => $termDays,
                        'term_days_max' => $termDaysMax,
                        'amount_min' => $amountMin,
                        'amount_max' => $amountMax,
                        'rate' => isset($term['rate']) && $term['rate'] !== '' ? (float) $term['rate'] : 0,
                        'is_active' => (bool) ($term['is_active'] ?? true),
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }
        }
    }
}
