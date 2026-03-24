<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Deposits\DepositResource;
use App\Models\Bank;
use App\Models\Deposit;
use App\Models\DepositCategory;
use App\Services\DepositConditionsMapper\DepositConditionsMapper;
use App\Services\Parsers\Sravni\SravniDepositParser;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SravniDepositsParsingPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationLabel = 'Вклады (Sravni)';

    protected static ?string $title = 'Парсинг вкладов (Sravni)';

    protected static ?string $slug = 'sravni-deposits-parsing';

    protected static string|\UnitEnum|null $navigationGroup = 'Парсинг';

    protected static ?int $navigationSort = 13;

    protected string $view = 'filament.pages.sravni-deposits-parsing';

    public ?string $depositUrl = 'https://www.sravni.ru/bank/bank-domrf/vklad/moy-dom/';

    public ?string $jsonResult = null;

    public ?string $logOutput = null;

    /** @var array<string, int>|null */
    public ?array $stats = null;

    public string $parseMode = 'only_new';

    public int $maxLimit = 200;

    /** @var array<int, array<string, mixed>> */
    public array $importResults = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('')
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('depositUrl')
                            ->label('URL страницы вклада')
                            ->placeholder('https://www.sravni.ru/bank/.../vklad/...')
                            ->helperText('Используется только кнопкой «Парсить одну страницу».')
                            ->url(),
                        Select::make('parseMode')
                            ->label('Режим')
                            ->options([
                                'only_new' => 'Только новые (only_new)',
                                'update_existing' => 'Обновлять существующие (update_existing)',
                            ])
                            ->default('only_new')
                            ->required(),
                        TextInput::make('maxLimit')
                            ->label('Максимум вкладов')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(2000)
                            ->default(200)
                            ->required(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('parseOne')
                ->label('Парсить одну страницу')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->action(function (): void {
                    $this->resetOutput();

                    $url = trim((string) $this->depositUrl);
                    if ($url === '') {
                        Notification::make()
                            ->title('URL не заполнен')
                            ->danger()
                            ->send();

                        return;
                    }

                    $parser = app(SravniDepositParser::class);
                    $result = $parser->parse($url);
                    $this->stats = $parser->getStats();

                    $this->jsonResult = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                    $this->logOutput = implode("\n", $parser->getLog());
                    $this->importResults = [];

                    if ($result === []) {
                        Notification::make()
                            ->title('Парсинг завершен с ошибками')
                            ->body('См. лог выполнения ниже.')
                            ->warning()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Парсинг завершен')
                        ->body('Получено записей: ' . count($result))
                        ->success()
                        ->send();
                }),

            Action::make('parseAll')
                ->label('Парсить все вклады')
                ->icon('heroicon-o-bars-3-bottom-left')
                ->color('success')
                ->action(function (): void {
                    $this->resetOutput();

                    $parser = app(SravniDepositParser::class);
                    $result = $parser->parseAll('https://www.sravni.ru/vklady/', (int) $this->maxLimit);
                    $result = $this->applyParseMode($result);
                    $this->stats = $parser->getStats();
                    $this->jsonResult = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                    $this->logOutput = implode("\n", $parser->getLog());
                    $this->importResults = [];

                    Notification::make()
                        ->title('Массовый парсинг завершен')
                        ->body('Найдено: ' . ($this->stats['found'] ?? 0) . '. Успешно: ' . ($this->stats['success'] ?? 0) . '. Ошибок: ' . ($this->stats['errors'] ?? 0))
                        ->success()
                        ->send();
                }),
            Action::make('importToDb')
                ->label('Импортировать в БД')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->action(function (): void {
                    $items = $this->decodeJsonResultItems();
                    if ($items === []) {
                        Notification::make()
                            ->title('Нет данных для импорта')
                            ->body('Сначала выполните парсинг.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $this->importResults = $this->importParsedItems($items, $this->parseMode);

                    $created = count(array_filter($this->importResults, fn (array $r): bool => ($r['status'] ?? '') === 'created'));
                    $updated = count(array_filter($this->importResults, fn (array $r): bool => ($r['status'] ?? '') === 'updated'));
                    $skipped = count(array_filter($this->importResults, fn (array $r): bool => ($r['status'] ?? '') === 'skipped'));

                    Notification::make()
                        ->title('Импорт завершен')
                        ->body("Создано: {$created}. Обновлено: {$updated}. Пропущено: {$skipped}.")
                        ->success()
                        ->send();
                }),
        ];
    }

    private function resetOutput(): void
    {
        $this->jsonResult = null;
        $this->logOutput = null;
        $this->stats = null;
        $this->importResults = [];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function applyParseMode(array $items): array
    {
        if ($this->parseMode !== 'only_new') {
            return $items;
        }

        $existingKeys = Deposit::query()
            ->with('bank:id,name')
            ->get(['id', 'bank_id', 'name'])
            ->map(function (Deposit $deposit): string {
                return $this->makeDuplicateKey((string) ($deposit->bank->name ?? ''), (string) $deposit->name);
            })
            ->filter()
            ->all();
        $existingMap = array_fill_keys($existingKeys, true);

        $filtered = [];
        foreach ($items as $item) {
            $key = $this->makeDuplicateKey((string) ($item['bank'] ?? ''), (string) ($item['deposit_name'] ?? ''));
            if ($key !== '' && isset($existingMap[$key])) {
                continue;
            }
            $filtered[] = $item;
        }

        return $filtered;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decodeJsonResultItems(): array
    {
        $raw = trim((string) $this->jsonResult);
        if ($raw === '') {
            return [];
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        if (! is_array($decoded)) {
            return [];
        }

        return array_is_list($decoded) ? $decoded : [$decoded];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function importParsedItems(array $items, string $mode): array
    {
        $banksByName = Bank::query()->get(['id', 'name'])->keyBy(fn (Bank $b): string => $this->normalizeText($b->name));
        $existingDeposits = Deposit::query()
            ->with('bank:id,name')
            ->get(['id', 'bank_id', 'name'])
            ->keyBy(fn (Deposit $d): string => $this->makeDuplicateKey((string) ($d->bank->name ?? ''), (string) $d->name));

        $categoryMap = DepositCategory::query()->get(['id', 'title'])->keyBy(fn (DepositCategory $c): string => $this->normalizeText($c->title));
        $results = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $bankName = trim((string) ($item['bank'] ?? ''));
            $depositName = trim((string) ($item['deposit_name'] ?? ''));
            if ($bankName === '' || $depositName === '') {
                $results[] = ['status' => 'skipped', 'message' => 'Пропущено: пустой bank/deposit_name', 'edit_url' => null];
                continue;
            }

            $bankKey = $this->normalizeText($bankName);
            $bank = $banksByName[$bankKey] ?? null;
            if (! $bank instanceof Bank) {
                $bank = Bank::create(['name' => $bankName, 'is_active' => true]);
                $banksByName[$bankKey] = $bank;
            }

            $dupKey = $this->makeDuplicateKey($bankName, $depositName);
            $deposit = $existingDeposits[$dupKey] ?? null;

            if ($mode === 'only_new' && $deposit instanceof Deposit) {
                $results[] = ['status' => 'skipped', 'message' => "Пропущено (уже существует): {$depositName} / {$bankName}", 'edit_url' => null];
                continue;
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
                $category = $categoryMap[$this->normalizeText($categoryValue)] ?? null;
                if ($category instanceof DepositCategory) {
                    $deposit->categories()->sync([$category->id]);
                }
            }

            $currenciesStructure = $this->toCurrenciesStructure($item['conditions'] ?? []);
            DepositConditionsMapper::fromFormStructure($deposit, $currenciesStructure);

            $existingDeposits[$dupKey] = $deposit->fresh(['bank:id,name']) ?? $deposit;
            $results[] = [
                'status' => $status,
                'message' => ($status === 'created' ? 'Создано: ' : 'Обновлено: ') . "{$depositName} / {$bankName}",
                'edit_url' => DepositResource::getUrl('edit', ['record' => $deposit]),
            ];
        }

        return $results;
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

