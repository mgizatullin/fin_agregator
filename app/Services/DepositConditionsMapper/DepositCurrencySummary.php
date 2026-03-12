<?php

namespace App\Services\DepositConditionsMapper;

use App\Models\DepositCurrency;
use Illuminate\Support\Collection;

/**
 * Вычисление сводки по валюте из условий: min/max ставка, срок, сумма.
 */
class DepositCurrencySummary
{
    /**
     * @return array{min_rate: float|null, max_rate: float|null, min_term: int|null, max_term: int|null, min_amount: float|null, max_amount: float|null}
     */
    public static function forCurrency(DepositCurrency $currency): array
    {
        $conditions = $currency->conditions->where('is_active', true)->values();
        if ($conditions->isEmpty()) {
            return [
                'min_rate' => null,
                'max_rate' => null,
                'min_term' => null,
                'max_term' => null,
                'min_amount' => null,
                'max_amount' => null,
            ];
        }

        $rates = $conditions->pluck('rate')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v);
        $termMins = $conditions->pluck('term_days_min')->filter(fn ($v) => $v !== null)->map(fn ($v) => (int) $v);
        $termMaxs = $conditions->pluck('term_days_max')->filter(fn ($v) => $v !== null)->map(fn ($v) => (int) $v);
        $allTerms = $termMins->merge($termMaxs)->filter();
        $amountMins = $conditions->pluck('amount_min')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v);
        $amountMaxs = $conditions->pluck('amount_max')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v);

        return [
            'min_rate' => $rates->isNotEmpty() ? $rates->min() : null,
            'max_rate' => $rates->isNotEmpty() ? $rates->max() : null,
            'min_term' => $allTerms->isNotEmpty() ? $allTerms->min() : null,
            'max_term' => $allTerms->isNotEmpty() ? $allTerms->max() : null,
            'min_amount' => $amountMins->isNotEmpty() ? $amountMins->min() : null,
            'max_amount' => $amountMaxs->isNotEmpty() ? $amountMaxs->max() : null,
        ];
    }

    /**
     * Лучшее предложение для карточки в списке: макс. ставка по RUB (или первой валюте).
     *
     * @return array{rate: float, term_days: int, amount_min: float|null, amount_label: string, currency_code: string}|null
     */
    public static function bestOfferForDeposit(\App\Models\Deposit $deposit): ?array
    {
        $deposit->loadMissing(['currencies.conditions']);
        $currencies = $deposit->currencies->filter(fn ($c) => $c->conditions->where('is_active', true)->isNotEmpty());
        if ($currencies->isEmpty()) {
            return null;
        }

        $currency = $currencies->firstWhere('currency_code', 'RUB') ?? $currencies->first();
        $conditions = $currency->conditions->where('is_active', true)->values();
        $best = $conditions->sortByDesc('rate')->first();
        if (! $best) {
            return null;
        }

        $termDays = (int) ($best->term_days_min ?? $best->term_days_max ?? 0);
        $amountMin = $best->amount_min !== null ? (float) $best->amount_min : null;
        $amountMax = $best->amount_max !== null ? (float) $best->amount_max : null;
        $amountLabel = self::formatAmountLabel($amountMin, $amountMax);

        return [
            'rate' => (float) $best->rate,
            'term_days' => $termDays,
            'amount_min' => $amountMin,
            'amount_label' => $amountLabel,
            'currency_code' => $currency->currency_code,
        ];
    }

    public static function formatAmountLabel(?float $min, ?float $max): string
    {
        if ($min !== null && $max !== null) {
            return 'от ' . number_format($min, 0, '', ' ') . ' до ' . number_format($max, 0, '', ' ');
        }
        if ($min !== null) {
            return 'от ' . number_format($min, 0, '', ' ');
        }
        if ($max !== null) {
            return 'до ' . number_format($max, 0, '', ' ');
        }
        return '—';
    }
}
