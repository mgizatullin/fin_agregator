<?php

namespace App\Services\Parsers\Sravni;

use App\Filament\Resources\Deposits\DepositResource;
use App\Models\Bank;
use App\Models\Deposit;
use App\Models\DepositCategory;
use App\Services\DepositConditionsMapper\DepositConditionsMapper;

class SravniDepositImporter
{
    /** @var array<string, Bank> */
    private array $banksByName = [];

    /** @var array<string, Deposit> */
    private array $existingDeposits = [];

    /** @var array<string, DepositCategory> */
    private array $categoryMap = [];

    public function __construct()
    {
        $this->banksByName = Bank::query()
            ->get(['id', 'name'])
            ->keyBy(fn (Bank $b): string => $this->normalizeText($b->name))
            ->all();

        $this->existingDeposits = Deposit::query()
            ->with('bank:id,name')
            ->get(['id', 'bank_id', 'name'])
            ->keyBy(fn (Deposit $d): string => $this->makeDuplicateKey((string) ($d->bank->name ?? ''), (string) $d->name))
            ->all();

        $this->categoryMap = DepositCategory::query()
            ->get(['id', 'title'])
            ->keyBy(fn (DepositCategory $c): string => $this->normalizeText($c->title))
            ->all();
    }

    /**
     * @param array<string, mixed> $item
     * @return array{status: string, message: string, edit_url: string|null, bank_created: bool}
     */
    public function importOne(array $item, string $mode): array
    {
        $bankName = trim((string) ($item['bank'] ?? ''));
        $depositName = trim((string) ($item['deposit_name'] ?? ''));
        if ($bankName === '' || $depositName === '') {
            return ['status' => 'skipped', 'message' => 'Пропущено: пустой bank/deposit_name', 'edit_url' => null, 'bank_created' => false];
        }

        $bankCreated = false;
        $bankKey = $this->normalizeText($bankName);
        $bank = $this->banksByName[$bankKey] ?? null;
        if (! $bank instanceof Bank) {
            $bank = Bank::create(['name' => $bankName, 'is_active' => true]);
            $this->banksByName[$bankKey] = $bank;
            $bankCreated = true;
        }

        $dupKey = $this->makeDuplicateKey($bankName, $depositName);
        $deposit = $this->existingDeposits[$dupKey] ?? null;

        if ($mode === 'only_new' && $deposit instanceof Deposit) {
            return ['status' => 'skipped', 'message' => "Пропущено (уже существует): {$depositName} / {$bankName}", 'edit_url' => null, 'bank_created' => $bankCreated];
        }

        $status = 'updated';
        if (! $deposit instanceof Deposit) {
            $deposit = new Deposit();
            $deposit->is_active = true;
            $status = 'created';
        }

        $features = is_array($item['features'] ?? null) ? $item['features'] : [];
        $deposit->fill([
            'name' => $depositName,
            'deposit_type' => (string) ($item['deposit_type'] ?? 'Срочный вклад'),
            'capitalization' => $this->toBool($features['capitalization'] ?? null),
            'online_opening' => $this->toBool($features['online_opening'] ?? null),
            'monthly_interest_payment' => $this->toBool($features['monthly_interest_payout'] ?? null),
            'partial_withdrawal' => $this->toBool($features['partial_withdrawal'] ?? null),
            'replenishment' => $this->toBool($features['replenishment'] ?? null),
            'early_termination' => $this->toBool($features['early_termination'] ?? null),
            'auto_prolongation' => $this->toBool($features['auto_prolongation'] ?? null),
            'insurance' => $this->toBool($features['insurance'] ?? true, true),
        ]);
        $deposit->bank_id = $bank->id;
        $deposit->save();

        $categoryValue = $item['category'] ?? null;
        if (is_string($categoryValue) && trim($categoryValue) !== '') {
            $category = $this->categoryMap[$this->normalizeText($categoryValue)] ?? null;
            if ($category instanceof DepositCategory) {
                $deposit->categories()->sync([$category->id]);
            }
        }

        $currenciesStructure = $this->toCurrenciesStructure($item['conditions'] ?? []);
        DepositConditionsMapper::fromFormStructure($deposit, $currenciesStructure);

        $this->existingDeposits[$dupKey] = $deposit->fresh(['bank:id,name']) ?? $deposit;

        return [
            'status' => $status,
            'message' => ($status === 'created' ? 'Создано: ' : 'Обновлено: ') . "{$depositName} / {$bankName}",
            'edit_url' => DepositResource::getUrl('edit', ['record' => $deposit]),
            'bank_created' => $bankCreated,
        ];
    }

    /**
     * @param mixed $conditions
     * @return array<int, array<string, mixed>>
     */
    private function toCurrenciesStructure(mixed $conditions): array
    {
        if (! is_array($conditions)) {
            return [];
        }

        $out = [];
        foreach ($conditions as $condition) {
            if (! is_array($condition)) {
                continue;
            }

            $currency = strtoupper(trim((string) ($condition['currency'] ?? 'RUB')));
            $ranges = is_array($condition['amount_ranges'] ?? null) ? $condition['amount_ranges'] : [];
            $normalizedRanges = [];
            foreach ($ranges as $range) {
                if (! is_array($range)) {
                    continue;
                }
                $terms = [];
                foreach (($range['terms'] ?? []) as $term) {
                    if (! is_array($term)) {
                        continue;
                    }
                    $days = isset($term['term_days']) && is_numeric((string) $term['term_days']) ? (int) $term['term_days'] : null;
                    $rate = isset($term['rate']) && is_numeric((string) $term['rate']) ? (float) $term['rate'] : null;
                    if ($days === null || $rate === null) {
                        continue;
                    }
                    $terms[] = ['term_days' => $days, 'rate' => $rate, 'is_active' => true];
                }
                usort($terms, fn (array $a, array $b): int => ((int) $a['term_days']) <=> ((int) $b['term_days']));

                $normalizedRanges[] = [
                    'amount_min' => isset($range['amount_from']) && is_numeric((string) $range['amount_from']) ? (float) $range['amount_from'] : null,
                    'amount_max' => isset($range['amount_to']) && is_numeric((string) $range['amount_to']) ? (float) $range['amount_to'] : null,
                    'terms' => $terms,
                ];
            }

            $out[] = ['currency_code' => $currency, 'amount_ranges' => $normalizedRanges];
        }

        return $out;
    }

    private function toBool(mixed $value, bool $default = false): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return ((int) $value) !== 0;
        }
        $normalized = mb_strtolower(trim((string) $value));
        if (in_array($normalized, ['true', 'yes', '1', 'да'], true)) {
            return true;
        }
        if (in_array($normalized, ['false', 'no', '0', 'нет'], true)) {
            return false;
        }

        return $default;
    }

    private function normalizeText(?string $value): string
    {
        $value = (string) $value;
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value) ?? $value));
    }

    private function makeDuplicateKey(?string $bankName, ?string $depositName): string
    {
        return $this->normalizeText($bankName) . '|' . $this->normalizeText($depositName);
    }
}

