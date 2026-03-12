<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\Deposit;
use App\Models\DepositCondition;
use App\Models\DepositCurrency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TestDepositsSeeder extends Seeder
{
    public function run(): void
    {
        $bank = Bank::query()->orderBy('id')->first();

        if (! $bank) {
            $bank = Bank::query()->create([
                'name' => 'Тестовый банк',
                'description' => 'Банк для тестовых вкладов и таблиц ставок.',
                'website' => 'https://example.com',
                'is_active' => true,
            ]);
        }

        $depositNames = [
            'Максимальный доход',
            'Надёжный',
            'Свободный',
            'Премиум',
            'Стратегический',
        ];

        $terms = [31, 91, 181, 365, 731, 1095];

        $rubRanges = [
            ['min' => 100_000, 'max' => 1_000_000],
            ['min' => 1_000_000, 'max' => null],
        ];

        $usdRanges = [
            ['min' => 5_000, 'max' => 50_000],
            ['min' => 50_000, 'max' => null],
        ];

        // Базовые ставки (как в ТЗ) для первого диапазона сумм.
        $rubBase = [31 => 14.5, 91 => 15.0, 181 => 14.3, 365 => 13.0, 731 => 11.8, 1095 => 10.9];
        $usdBase = [31 => 2.0, 91 => 2.2, 181 => 2.1, 365 => 2.0, 731 => 1.8, 1095 => 1.6];

        foreach ($depositNames as $i => $name) {
            $slug = Str::slug($name);

            /** @var Deposit $deposit */
            $deposit = Deposit::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'bank_id' => $bank->id,
                    'name' => 'Вклад "' . $name . '"',
                    'description' => '<p>Тестовый вклад для проверки таблицы ставок, табов валют и группировки по суммам/срокам.</p>',
                    'capitalization' => (bool) (($i % 2) === 0),
                    'replenishment' => (bool) (($i % 3) !== 0),
                    'partial_withdrawal' => (bool) (($i % 4) === 0),
                    'online_opening' => true,
                    'monthly_interest_payment' => (bool) (($i % 2) === 1),
                    'early_termination' => true,
                    'auto_prolongation' => (bool) (($i % 2) === 0),
                    'insurance' => true,
                    'is_active' => true,
                ],
            );

            // Полностью пересоздаём валюты/условия — это тестовые данные.
            $deposit->currencies()->delete();

            $variation = ($i - 2) * 0.05; // небольшая разница между вкладами

            $this->seedCurrency(
                deposit: $deposit,
                currencyCode: 'RUB',
                ranges: $rubRanges,
                terms: $terms,
                baseRates: $rubBase,
                secondRangeBump: 0.30,
                variation: $variation,
            );

            $this->seedCurrency(
                deposit: $deposit,
                currencyCode: 'USD',
                ranges: $usdRanges,
                terms: $terms,
                baseRates: $usdBase,
                secondRangeBump: 0.20,
                variation: $variation / 2,
            );

            // Для 2 вкладов добавим EUR.
            if (in_array($i, [0, 3], true)) {
                $eurBase = [31 => 1.6, 91 => 1.8, 181 => 1.7, 365 => 1.6, 731 => 1.4, 1095 => 1.2];
                $eurRanges = [
                    ['min' => 3_000, 'max' => 30_000],
                    ['min' => 30_000, 'max' => null],
                ];

                $this->seedCurrency(
                    deposit: $deposit,
                    currencyCode: 'EUR',
                    ranges: $eurRanges,
                    terms: $terms,
                    baseRates: $eurBase,
                    secondRangeBump: 0.15,
                    variation: $variation / 2,
                );
            }

            $deposit->load(['currencies.conditions']);
        }
    }

    /**
     * @param array<int, array{min: int, max: int|null}> $ranges
     * @param array<int, int> $terms
     * @param array<int, float> $baseRates
     */
    private function seedCurrency(
        Deposit $deposit,
        string $currencyCode,
        array $ranges,
        array $terms,
        array $baseRates,
        float $secondRangeBump,
        float $variation,
    ): void {
        /** @var DepositCurrency $currency */
        $currency = $deposit->currencies()->create([
            'currency_code' => $currencyCode,
            'min_amount' => null,
            'max_amount' => null,
            'sort_order' => match ($currencyCode) {
                'RUB' => 0,
                'USD' => 1,
                'EUR' => 2,
                default => 10,
            },
        ]);

        $sortOrder = 0;

        foreach ($ranges as $rangeIndex => $range) {
            $bump = $rangeIndex === 1 ? $secondRangeBump : 0.0;

            foreach ($terms as $termDays) {
                $rate = ($baseRates[$termDays] ?? 0.0) + $bump + $variation;
                $rate = max(0.01, round($rate, 2));

                $currency->conditions()->create([
                    'amount_min' => (float) $range['min'],
                    'amount_max' => $range['max'] !== null ? (float) $range['max'] : null,
                    'term_days_min' => (int) $termDays,
                    'term_days_max' => (int) $termDays,
                    'rate' => $rate,
                    'is_active' => true,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }
    }
}

