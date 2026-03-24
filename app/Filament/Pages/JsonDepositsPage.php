<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Deposits\DepositResource;
use App\Models\Bank;
use App\Models\Deposit;
use App\Models\DepositCategory;
use App\Services\DepositConditionsMapper\DepositConditionsMapper;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class JsonDepositsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-code-bracket-square';

    protected static ?string $navigationLabel = 'JSON - Вклады';

    protected static ?string $title = 'JSON - Вклады';

    protected static ?string $slug = 'json-deposits';

    protected static string|\UnitEnum|null $navigationGroup = 'Парсинг';

    protected static ?int $navigationSort = 12;

    protected string $view = 'filament.pages.json-deposits-page';

    public ?string $jsonInput = null;

    /** @var array<int, array<string, mixed>> */
    public array $recognizedItems = [];

    /** @var array<string, mixed>|null */
    public ?array $recognizeReport = null;

    /** @var array<int, array<string, mixed>> */
    public array $importResults = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('')
            ->components([
                Section::make()
                    ->schema([
                        Textarea::make('jsonInput')
                            ->label('JSON массив вкладов')
                            ->rows(18)
                            ->placeholder('[{"bank":"Банк","deposit_name":"Вклад"}]')
                            ->helperText('Поддерживается массив JSON, один объект JSON или список объектов без внешних квадратных скобок.'),
                    ]),
            ]);
    }

    public function recognizeJson(): void
    {
        $this->importResults = [];

        try {
            $items = $this->decodeJsonInput();
        } catch (\RuntimeException $e) {
            $this->recognizedItems = [];
            $this->recognizeReport = null;

            Notification::make()
                ->title('Не удалось распознать JSON')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        [$categoryMap, $categoryTitlesById] = $this->buildDepositCategoryMap();
        $recognizedItems = [];
        $readyCount = 0;
        $errorCount = 0;
        $warningCount = 0;

        foreach ($items as $index => $item) {
            $row = $this->recognizeItem($item, $index, $categoryMap, $categoryTitlesById);
            $recognizedItems[] = $row;

            $errorCount += count($row['errors']);
            $warningCount += count($row['warnings']);

            if ($row['ready']) {
                $readyCount++;
            }
        }

        $this->recognizedItems = $recognizedItems;
        $this->recognizeReport = [
            'total' => count($recognizedItems),
            'ready' => $readyCount,
            'errors' => $errorCount,
            'warnings' => $warningCount,
        ];

        Notification::make()
            ->title('Распознавание завершено')
            ->body("Элементов: {$this->recognizeReport['total']}. Готово к импорту: {$readyCount}. Ошибок: {$errorCount}.")
            ->success()
            ->send();
    }

    public function importToDatabase(): void
    {
        if ($this->recognizedItems === []) {
            $this->recognizeJson();
        }

        if ($this->recognizedItems === [] || $this->recognizeReport === null) {
            return;
        }

        $banksByName = Bank::query()
            ->get(['id', 'name'])
            ->keyBy(fn (Bank $bank): string => $this->normalizeText($bank->name));

        $existingDeposits = Deposit::query()
            ->with(['bank:id,name'])
            ->get(['id', 'bank_id', 'name'])
            ->keyBy(function (Deposit $deposit): string {
                $bankName = (string) ($deposit->bank->name ?? '');

                return $this->makeDepositDuplicateKey($bankName, $deposit->name);
            });

        $results = [];
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($this->recognizedItems as $item) {
            if (! ($item['ready'] ?? false)) {
                $results[] = [
                    'status' => 'skipped',
                    'message' => 'Пропущено: есть ошибки распознавания у записи #' . (($item['index'] ?? 0) + 1),
                    'edit_url' => null,
                ];
                $skipped++;

                continue;
            }

            $payload = $item['payload'] ?? [];
            $lookup = $item['lookup'] ?? [];
            $bankName = (string) ($lookup['bank'] ?? '');
            $depositName = (string) ($lookup['name'] ?? '');

            $bankKey = $this->normalizeText($bankName);
            $bank = $banksByName[$bankKey] ?? null;
            if (! $bank instanceof Bank) {
                $bank = Bank::create([
                    'name' => $bankName,
                    'is_active' => true,
                ]);
                $banksByName[$bankKey] = $bank;
            }

            $key = $this->makeDepositDuplicateKey($bankName, $depositName);
            $deposit = $existingDeposits[$key] ?? null;
            $status = 'updated';

            if (! $deposit instanceof Deposit) {
                $deposit = new Deposit();
                $deposit->is_active = true;
                $status = 'created';
            }

            $deposit->fill($payload);
            $deposit->bank_id = $bank->id;
            $deposit->save();

            if (($item['has_categories'] ?? false) === true) {
                $categoryIds = array_values(array_unique(array_map('intval', $item['category_ids'] ?? [])));
                $deposit->categories()->sync($categoryIds);
            }

            DepositConditionsMapper::fromFormStructure($deposit, $item['currencies_structure'] ?? []);

            $existingDeposits[$key] = $deposit->fresh(['bank:id,name']) ?? $deposit;

            if ($status === 'created') {
                $created++;
            } else {
                $updated++;
            }

            $results[] = [
                'status' => $status,
                'message' => ($status === 'created' ? 'Создано' : 'Обновлено') . ': ' . $deposit->name . ' / ' . $bank->name,
                'edit_url' => DepositResource::getUrl('edit', ['record' => $deposit]),
            ];
        }

        $this->importResults = $results;

        Notification::make()
            ->title('Импорт завершён')
            ->body("Создано: {$created}. Обновлено: {$updated}. Пропущено: {$skipped}.")
            ->success()
            ->send();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decodeJsonInput(): array
    {
        $raw = trim((string) $this->jsonInput);
        if ($raw === '') {
            throw new \RuntimeException('Поле JSON пустое.');
        }

        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;
        $trimmed = trim($raw);
        $candidates = [
            $trimmed,
            preg_replace('/,\s*([\]}])/u', '$1', $trimmed) ?? $trimmed,
        ];

        $wrapped = '[' . trim($trimmed, ", \n\r\t") . ']';
        $candidates[] = $wrapped;
        $candidates[] = preg_replace('/,\s*([\]}])/u', '$1', $wrapped) ?? $wrapped;

        foreach (array_values(array_unique($candidates)) as $candidate) {
            try {
                $decoded = json_decode($candidate, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                continue;
            }

            if (! is_array($decoded)) {
                continue;
            }

            if (array_is_list($decoded)) {
                return $decoded;
            }

            return [$decoded];
        }

        throw new \RuntimeException('JSON не удалось декодировать. Проверьте формат массива и кавычки.');
    }

    /**
     * @param  mixed  $item
     * @param  array<string, array<int, int>>  $categoryMap
     * @param  array<int, string>  $categoryTitlesById
     * @return array<string, mixed>
     */
    private function recognizeItem(mixed $item, int $index, array $categoryMap, array $categoryTitlesById): array
    {
        $errors = [];
        $warnings = [];

        if (! is_array($item)) {
            return [
                'index' => $index,
                'source_name' => '—',
                'ready' => false,
                'errors' => ['Элемент не является JSON-объектом.'],
                'warnings' => [],
                'category_ids' => [],
                'payload' => [],
                'currencies_structure' => [],
            ];
        }

        $bankName = trim((string) ($item['bank'] ?? ''));
        $depositName = trim((string) ($item['deposit_name'] ?? ''));
        $depositType = trim((string) ($item['deposit_type'] ?? ''));

        if ($bankName === '') {
            $errors[] = 'Не заполнено поле bank.';
        }

        if ($depositName === '') {
            $errors[] = 'Не заполнено поле deposit_name.';
        }

        $features = is_array($item['features'] ?? null) ? $item['features'] : [];
        if (($item['features'] ?? null) !== null && ! is_array($item['features'])) {
            $warnings[] = 'Поле features должно быть объектом.';
        }

        $payload = [
            'name' => $depositName,
            'deposit_type' => $depositType !== '' ? $depositType : null,
            // В SQLite эти флаги у вкладов NOT NULL, поэтому null приводим к false.
            'capitalization' => $this->toBool($features['capitalization'] ?? null, false),
            'online_opening' => $this->toBool($features['online_opening'] ?? null, false),
            'monthly_interest_payment' => $this->toBool($features['monthly_interest_payout'] ?? null, false),
            'partial_withdrawal' => $this->toBool($features['partial_withdrawal'] ?? null, false),
            'replenishment' => $this->toBool($features['replenishment'] ?? null, false),
            'early_termination' => $this->toBool($features['early_termination'] ?? null, false),
            'auto_prolongation' => $this->toBool($features['auto_prolongation'] ?? null, false),
            'insurance' => $this->toBool($features['insurance'] ?? null, false),
        ];

        if (isset($item['description']) && trim((string) $item['description']) !== '') {
            $payload['description'] = (string) $item['description'];
        }

        [$categoryIds, $matchedCategoryTitles, $unknownCategories, $categoryWarnings] = $this->resolveCategories($item['category'] ?? null, $categoryMap, $categoryTitlesById);
        $warnings = array_merge($warnings, $categoryWarnings);

        $conditions = $item['conditions'] ?? null;
        $currenciesStructure = [];
        if (! is_array($conditions)) {
            $errors[] = 'Поле conditions должно быть массивом.';
        } else {
            foreach ($conditions as $conditionIndex => $condition) {
                if (! is_array($condition)) {
                    $errors[] = 'Элемент conditions[' . $conditionIndex . '] должен быть объектом.';
                    continue;
                }

                $currencyCode = trim((string) ($condition['currency'] ?? ''));
                if ($currencyCode === '') {
                    $errors[] = 'Не заполнено поле currency в conditions[' . $conditionIndex . '].';
                    continue;
                }

                $amountRanges = $condition['amount_ranges'] ?? null;
                if (! is_array($amountRanges)) {
                    $errors[] = 'Поле amount_ranges должно быть массивом в conditions[' . $conditionIndex . '].';
                    continue;
                }

                $rangeRows = [];
                foreach ($amountRanges as $rangeIndex => $range) {
                    if (! is_array($range)) {
                        $errors[] = 'Элемент amount_ranges[' . $rangeIndex . '] должен быть объектом.';
                        continue;
                    }

                    $amountMin = $this->parseDecimal($range['amount_from'] ?? null, "conditions[{$conditionIndex}].amount_ranges[{$rangeIndex}].amount_from", $errors);
                    $amountMax = $this->parseDecimal($range['amount_to'] ?? null, "conditions[{$conditionIndex}].amount_ranges[{$rangeIndex}].amount_to", $errors);

                    if ($amountMin !== null && $amountMax !== null && $amountMin > $amountMax) {
                        $errors[] = "amount_from больше amount_to в conditions[{$conditionIndex}].amount_ranges[{$rangeIndex}]";
                    }

                    $terms = $range['terms'] ?? null;
                    if (! is_array($terms)) {
                        $errors[] = 'Поле terms должно быть массивом в conditions[' . $conditionIndex . '].amount_ranges[' . $rangeIndex . '].';
                        continue;
                    }

                    $termRows = [];
                    foreach ($terms as $termIndex => $term) {
                        if (! is_array($term)) {
                            $errors[] = 'Элемент terms[' . $termIndex . '] должен быть объектом.';
                            continue;
                        }

                        $termDays = $this->parseInteger($term['term_days'] ?? null, "conditions[{$conditionIndex}].amount_ranges[{$rangeIndex}].terms[{$termIndex}].term_days", $errors);
                        $rate = $this->parseDecimal($term['rate'] ?? null, "conditions[{$conditionIndex}].amount_ranges[{$rangeIndex}].terms[{$termIndex}].rate", $errors);

                        if ($termDays === null || $rate === null) {
                            continue;
                        }

                        $termRows[] = [
                            'term_days' => $termDays,
                            'rate' => $rate,
                            'is_active' => true,
                        ];
                    }

                    usort($termRows, fn (array $a, array $b): int => ((int) $a['term_days']) <=> ((int) $b['term_days']));

                    if ($termRows === []) {
                        $warnings[] = 'Для диапазона сумм не найдено корректных terms: conditions[' . $conditionIndex . '].amount_ranges[' . $rangeIndex . '].';
                    }

                    $rangeRows[] = [
                        'amount_min' => $amountMin,
                        'amount_max' => $amountMax,
                        'terms' => $termRows,
                    ];
                }

                $currenciesStructure[] = [
                    'currency_code' => strtoupper($currencyCode),
                    'amount_ranges' => $rangeRows,
                ];
            }
        }

        return [
            'index' => $index,
            'source_name' => ($bankName !== '' ? $bankName . ' / ' : '') . ($depositName !== '' ? $depositName : '—'),
            'ready' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
            'lookup' => [
                'bank' => $bankName,
                'name' => $depositName,
            ],
            'payload' => $payload,
            'category_ids' => $categoryIds,
            'matched_categories' => $matchedCategoryTitles,
            'unknown_categories' => $unknownCategories,
            'has_categories' => ($item['category'] ?? null) !== null,
            'currencies_structure' => $currenciesStructure,
        ];
    }

    /**
     * @return array{0: array<string, array<int, int>>, 1: array<int, string>}
     */
    private function buildDepositCategoryMap(): array
    {
        $map = [];
        $titlesById = [];

        foreach (DepositCategory::query()->get(['id', 'title', 'h1_template']) as $category) {
            $titlesById[(int) $category->id] = (string) $category->title;

            foreach ([$category->title, $category->h1_template] as $value) {
                $normalized = $this->normalizeCategoryName((string) $value);
                if ($normalized === '') {
                    continue;
                }

                $map[$normalized] ??= [];
                $map[$normalized][] = (int) $category->id;
            }
        }

        return [$map, $titlesById];
    }

    /**
     * @return array{0: array<int, int>, 1: array<int, string>, 2: array<int, string>, 3: array<int, string>}
     */
    private function resolveCategories(mixed $categoryValue, array $categoryMap, array $categoryTitlesById): array
    {
        $warnings = [];
        $categoryIds = [];
        $unknownCategories = [];

        $categoryNames = [];
        if (is_string($categoryValue) && trim($categoryValue) !== '') {
            $categoryNames = [trim($categoryValue)];
        } elseif (is_array($categoryValue)) {
            foreach ($categoryValue as $name) {
                if (is_string($name) && trim($name) !== '') {
                    $categoryNames[] = trim($name);
                }
            }
        } elseif ($categoryValue !== null) {
            $warnings[] = 'Поле category должно быть строкой или массивом строк.';
        }

        foreach ($categoryNames as $categoryName) {
            $normalized = $this->normalizeCategoryName($categoryName);
            $matched = $categoryMap[$normalized] ?? [];

            if ($matched === []) {
                $unknownCategories[] = $categoryName;
                $warnings[] = 'Категория не найдена: ' . $categoryName;
                continue;
            }

            $categoryIds = array_merge($categoryIds, $matched);
        }

        $categoryIds = array_values(array_unique(array_map('intval', $categoryIds)));
        $matchedCategoryTitles = array_values(array_map(
            fn (int $id): string => $categoryTitlesById[$id] ?? ('#' . $id),
            $categoryIds,
        ));

        return [$categoryIds, $matchedCategoryTitles, $unknownCategories, $warnings];
    }

    private function normalizeCategoryName(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['Ё', 'ё'], ['е', 'е'], $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
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
            return (int) $value !== 0;
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

    private function parseInteger(mixed $value, string $field, array &$errors): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $normalized = str_replace([' ', "\xc2\xa0"], '', trim((string) $value));
        if (! preg_match('/^-?\d+$/', $normalized)) {
            $errors[] = "Поле {$field} должно быть целым числом.";

            return null;
        }

        return (int) $normalized;
    }

    private function parseDecimal(mixed $value, string $field, array &$errors): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $normalized = trim((string) $value);
        $normalized = str_replace(["\xc2\xa0", ' '], '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        if (! is_numeric($normalized)) {
            $errors[] = "Поле {$field} должно быть числом.";

            return null;
        }

        return (float) $normalized;
    }

    private function makeDepositDuplicateKey(?string $bankName, ?string $depositName): string
    {
        return $this->normalizeText($bankName) . '|' . $this->normalizeText($depositName);
    }

    private function normalizeText(?string $value): string
    {
        $value = (string) $value;
        $value = str_replace(['Ё', 'ё', '«', '»', '"'], ['Е', 'е', '', '', ''], $value);

        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value) ?? $value));
    }
}

