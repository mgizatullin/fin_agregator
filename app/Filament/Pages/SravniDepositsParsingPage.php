<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Deposits\DepositResource;
use App\Models\Bank;
use App\Models\Deposit;
use App\Models\DepositCategory;
use App\Models\ParserRun;
use App\Jobs\SravniParseUrlsJob;
use App\Services\DepositConditionsMapper\DepositConditionsMapper;
use App\Services\Parsers\Sravni\SravniDepositParser;
use App\Services\Parsers\Sravni\SravniDepositImporter;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class SravniDepositsParsingPage extends Page implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    private const PARSER_KEY = 'sravni_deposits';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationLabel = 'Вклады (Sravni)';

    protected static ?string $title = 'Парсинг вкладов (Sravni)';

    protected static ?string $slug = 'sravni-deposits-parsing';

    protected static string|\UnitEnum|null $navigationGroup = 'Парсинг';

    protected static ?int $navigationSort = 13;

    protected string $view = 'filament.pages.sravni-deposits-parsing';

    public ?string $jsonResult = null;

    public ?string $logOutput = null;

    /** @var array<string, int>|null */
    public ?array $stats = null;

    public ?string $lastParsedAt = null;

    public ?int $lastRunId = null;

    public string $parseMode = 'upsert';

    public int $maxLimit = 200;

    public ?string $depositUrlsInput = null;

    public int $requestDelaySeconds = 1;

    public ?int $activeRunId = null;

    /**
     * FileUpload state can be array even for single file (catalogJsonFile.0),
     * поэтому держим как массив.
     */
    public $catalogJsonFile = [];

    /** @var array<int, array<string, mixed>> */
    public array $importResults = [];

    public function mount(): void
    {
        $this->hydrateFromLastRun();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('')
            ->components([
                Section::make()
                    ->schema([
                        Textarea::make('depositUrlsInput')
                            ->label('URLы вкладов (массово)')
                            ->rows(8)
                            ->placeholder("Вставьте список URLов, по 1 на строку.\nНапример:\nhttps://www.sravni.ru/bank/sberbank-rossii/vklad/sbervklad--ezhemesyachno/\nhttps://www.sravni.ru/bank/tinkoff/vklad/smart/")
                            ->helperText('Используется кнопкой «Парсить URLы (массово)».'),
                        Select::make('parseMode')
                            ->label('Режим')
                            ->options([
                                'only_new' => 'Только новые (only_new)',
                                'update_existing' => 'Обновлять существующие (update_existing)',
                                'upsert' => 'Добавлять и обновлять (upsert)',
                            ])
                            ->default('upsert')
                            ->required()
                            ->placeholder('Выберите режим'),
                        TextInput::make('maxLimit')
                            ->label('Максимум вкладов')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(2000)
                            ->default(200)
                            ->required(),
                        TextInput::make('requestDelaySeconds')
                            ->label('Пауза между запросами (сек)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->default(1)
                            ->helperText('Рекомендуется 1–2 сек, чтобы снизить риск блокировки.'),
                        FileUpload::make('catalogJsonFile')
                            ->label('JSON выгрузка каталога Sravni')
                            ->helperText('Файл ~2 МБ — это нормально. Нажмите «Распознать JSON», затем «Сформировать URLы».')
                            ->disk('public')
                            ->directory('imports/sravni')
                            ->visibility('public')
                            ->preserveFilenames()
                            ->acceptedFileTypes(['application/json', 'text/json', 'text/plain'])
                            ->maxSize(8192),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    private function resetOutput(): void
    {
        $this->jsonResult = null;
        $this->logOutput = null;
        $this->stats = null;
        $this->importResults = [];
    }

    private function catalogJsonUploadedPath(): string
    {
        $v = $this->catalogJsonFile;

        if (is_string($v)) {
            return trim($v);
        }

        if (is_array($v)) {
            $first = null;
            foreach ($v as $item) {
                $first = $item;
                break;
            }

            if (is_string($first)) {
                return trim($first);
            }

            if ($first instanceof UploadedFile) {
                // Ensure it's persisted, because FileUpload state can still be a temporary upload object.
                $stored = $first->store('imports/sravni', 'public');
                $this->catalogJsonFile = [$stored];
                return trim((string) $stored);
            }

            return '';
        }

        return '';
    }

    private function recognizeCatalogJsonFromUpload(): void
    {
        $this->resetOutput();

        $path = $this->catalogJsonUploadedPath();
        if ($path === '') {
            $this->logOutput = 'catalogJsonFile state: ' . get_debug_type($this->catalogJsonFile) . "\n"
                . 'value: ' . mb_substr(json_encode($this->catalogJsonFile, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '—', 0, 500);
            Notification::make()
                ->title('JSON файл не загружен')
                ->body('Состояние поля не содержит путь. См. лог ниже.')
                ->warning()
                ->send();
            return;
        }

        if (! Storage::disk('public')->exists($path)) {
            Notification::make()
                ->title('JSON файл не найден')
                ->body('Попробуйте загрузить файл заново.')
                ->danger()
                ->send();
            return;
        }

        $raw = Storage::disk('public')->get($path);
        if (! is_string($raw) || trim($raw) === '') {
            $this->stats = ['found' => 0, 'processed' => 0, 'success' => 0, 'errors' => 1];
            $this->logOutput = 'Файл пустой или не удалось прочитать.';
            $this->persistRun('catalog_json_recognize', ['file' => $path]);

            Notification::make()
                ->title('JSON не распознан')
                ->body('Файл пустой.')
                ->danger()
                ->send();
            return;
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->stats = ['found' => 0, 'processed' => 0, 'success' => 0, 'errors' => 1];
            $this->logOutput = 'Ошибка JSON: ' . $e->getMessage();
            $this->persistRun('catalog_json_recognize', ['file' => $path]);

            Notification::make()
                ->title('JSON не распознан')
                ->body('Ошибка декодирования JSON. См. лог.')
                ->danger()
                ->send();
            return;
        }

        $pairs = $this->extractDepositAliasPairs($decoded);
        $errors = $this->countAliasPairErrors($pairs);

        $this->stats = [
            'found' => count($pairs),
            'processed' => count($pairs),
            'success' => max(0, count($pairs) - $errors),
            'errors' => $errors,
        ];

        $this->logOutput = implode("\n", [
            'Распознавание JSON завершено.',
            'Найдено позиций: ' . ($this->stats['found'] ?? 0),
            'С ошибками (без alias): ' . ($this->stats['errors'] ?? 0),
        ]);

        $this->persistRun('catalog_json_recognize', [
            'file' => $path,
            'found' => $this->stats['found'],
            'errors' => $this->stats['errors'],
        ]);

        Notification::make()
            ->title('JSON распознан')
            ->body('Найдено позиций: ' . ($this->stats['found'] ?? 0) . '. Ошибок: ' . ($this->stats['errors'] ?? 0))
            ->success()
            ->send();
    }

    private function buildDepositUrlsFromUpload(): void
    {
        $this->resetOutput();

        $path = $this->catalogJsonUploadedPath();
        if ($path === '') {
            $this->logOutput = 'catalogJsonFile state: ' . get_debug_type($this->catalogJsonFile) . "\n"
                . 'value: ' . mb_substr(json_encode($this->catalogJsonFile, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '—', 0, 500);
            Notification::make()
                ->title('JSON файл не загружен')
                ->body('Состояние поля не содержит путь. См. лог ниже.')
                ->warning()
                ->send();
            return;
        }

        if (! Storage::disk('public')->exists($path)) {
            Notification::make()
                ->title('JSON файл не найден')
                ->body('Попробуйте загрузить файл заново.')
                ->danger()
                ->send();
            return;
        }

        $raw = Storage::disk('public')->get($path);
        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->stats = ['found' => 0, 'processed' => 0, 'success' => 0, 'errors' => 1];
            $this->logOutput = 'Ошибка JSON: ' . $e->getMessage();
            $this->persistRun('catalog_json_urls', ['file' => $path]);

            Notification::make()
                ->title('Не удалось сформировать URLы')
                ->body('Ошибка декодирования JSON. См. лог.')
                ->danger()
                ->send();
            return;
        }

        $pairs = $this->extractDepositAliasPairs($decoded);

        $urls = [];
        $errors = 0;
        foreach ($pairs as $row) {
            $bankAlias = trim((string) ($row['bank_alias'] ?? ''));
            $productAlias = trim((string) ($row['product_alias'] ?? ''));
            if ($bankAlias === '' || $productAlias === '') {
                $errors++;
                continue;
            }
            $urls[] = 'https://www.sravni.ru/bank/' . $bankAlias . '/vklad/' . $productAlias . '/';
        }
        $urls = array_values(array_unique($urls));

        $this->stats = [
            'found' => count($pairs),
            'processed' => count($pairs),
            'success' => count($urls),
            'errors' => $errors,
        ];

        $this->logOutput = implode("\n", [
            'Формирование URLов завершено.',
            'Позиции в JSON: ' . ($this->stats['found'] ?? 0),
            'Сформировано URLов: ' . ($this->stats['success'] ?? 0),
            'Ошибок (без alias): ' . ($this->stats['errors'] ?? 0),
        ]);

        $this->jsonResult = json_encode($urls, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $this->persistRun('catalog_json_urls', [
            'file' => $path,
            'found' => $this->stats['found'],
            'urls' => $this->stats['success'],
            'errors' => $this->stats['errors'],
        ]);

        Notification::make()
            ->title('URLы сформированы')
            ->body('Сформировано: ' . ($this->stats['success'] ?? 0) . '. Ошибок: ' . ($this->stats['errors'] ?? 0))
            ->success()
            ->send();
    }

    public function startUrlsBulk(): void
    {
        $this->resetOutput();

        $raw = trim((string) $this->depositUrlsInput);
        if ($raw === '') {
            Notification::make()
                ->title('Список URLов пуст')
                ->warning()
                ->send();
            return;
        }

        $lines = preg_split('/\r\n|\r|\n/u', $raw) ?: [];
        $urls = [];
        foreach ($lines as $line) {
            $u = trim((string) $line);
            if ($u === '') {
                continue;
            }
            $urls[] = $u;
        }
        $urls = array_values(array_unique($urls));

        $limit = (int) $this->maxLimit;
        if ($limit > 0 && count($urls) > $limit) {
            $urls = array_slice($urls, 0, $limit);
        }

        $delay = (int) $this->requestDelaySeconds;
        if ($delay < 0) {
            $delay = 0;
        }
        if ($delay > 10) {
            $delay = 10;
        }

        $this->stats = [
            'found' => count($urls),
            'processed' => 0,
            'success' => 0,
            'errors' => 0,
        ];
        $this->logOutput = 'Задача поставлена в очередь. URLов: ' . count($urls);
        $this->jsonResult = null;
        $this->importResults = [];

        $run = ParserRun::create([
            'parser_key' => self::PARSER_KEY,
            'mode' => 'parse_urls_bulk_queued',
            'params' => [
                'parse_mode' => $this->parseMode,
                'max_limit' => (int) $this->maxLimit,
                'delay_seconds' => $delay,
                'urls' => $urls,
            ],
            'stats' => $this->stats,
            'result_json' => null,
            'log_output' => $this->logOutput,
        ]);

        $this->activeRunId = (int) $run->id;
        $this->lastRunId = (int) $run->id;
        $this->lastParsedAt = $run->created_at?->format('d.m.Y H:i:s');

        SravniParseUrlsJob::dispatch((int) $run->id);

        Notification::make()
            ->title('Массовый парсинг запущен')
            ->body('Можно наблюдать прогресс на странице. URLов: ' . count($urls))
            ->success()
            ->send();
    }

    public function refreshProgress(): void
    {
        $id = (int) ($this->activeRunId ?? 0);
        if ($id < 1) {
            return;
        }

        $run = ParserRun::query()->find($id);
        if (! $run instanceof ParserRun) {
            return;
        }

        $this->stats = is_array($run->stats) ? $run->stats : null;
        $this->logOutput = is_string($run->log_output) ? $run->log_output : null;

        if (in_array((string) $run->mode, ['parse_urls_bulk_done', 'parse_urls_bulk_cancelled'], true)) {
            $this->activeRunId = null;
        }
    }

    public function stopActiveRun(): void
    {
        $id = (int) ($this->activeRunId ?? 0);
        if ($id < 1) {
            return;
        }

        $run = ParserRun::query()->find($id);
        if (! $run instanceof ParserRun) {
            return;
        }

        $params = is_array($run->params) ? $run->params : [];
        $params['cancel_requested'] = true;
        $run->params = $params;
        $run->save();

        Notification::make()
            ->title('Остановка запрошена')
            ->body('Задача остановится после завершения текущего запроса.')
            ->warning()
            ->send();
    }

    /**
     * @return array<int, array{bank_alias: string|null, product_alias: string|null}>
     */
    private function extractDepositAliasPairs(mixed $node): array
    {
        $out = [];
        $this->collectDepositAliasPairs($node, $out);

        $unique = [];
        foreach ($out as $row) {
            $bankAlias = is_string($row['bank_alias'] ?? null) ? trim($row['bank_alias']) : '';
            $productAlias = is_string($row['product_alias'] ?? null) ? trim($row['product_alias']) : '';
            $key = $bankAlias . '|' . $productAlias;
            if (isset($unique[$key])) {
                continue;
            }
            $unique[$key] = [
                'bank_alias' => $bankAlias !== '' ? $bankAlias : null,
                'product_alias' => $productAlias !== '' ? $productAlias : null,
            ];
        }

        return array_values($unique);
    }

    /**
     * @param array<int, array{bank_alias: string|null, product_alias: string|null}> $items
     */
    private function countAliasPairErrors(array $items): int
    {
        $errors = 0;
        foreach ($items as $row) {
            $bankAlias = trim((string) ($row['bank_alias'] ?? ''));
            $productAlias = trim((string) ($row['product_alias'] ?? ''));
            if ($bankAlias === '' || $productAlias === '') {
                $errors++;
            }
        }
        return $errors;
    }

    /**
     * @param mixed $node
     * @param array<int, array{bank_alias: string|null, product_alias: string|null}> $out
     */
    private function collectDepositAliasPairs(mixed $node, array &$out): void
    {
        if (! is_array($node)) {
            return;
        }

        $bankAlias = null;
        $productAlias = null;

        if (isset($node['bankDetail']) && is_array($node['bankDetail'])) {
            $candidate = $node['bankDetail']['alias'] ?? null;
            if (is_string($candidate) && trim($candidate) !== '') {
                $bankAlias = trim($candidate);
            }
        }
        if (isset($node['product']) && is_array($node['product'])) {
            $candidate = $node['product']['alias'] ?? null;
            if (is_string($candidate) && trim($candidate) !== '') {
                $productAlias = trim($candidate);
            }
        }

        if ($bankAlias !== null || $productAlias !== null) {
            $out[] = ['bank_alias' => $bankAlias, 'product_alias' => $productAlias];
        }

        foreach ($node as $value) {
            if (is_array($value)) {
                $this->collectDepositAliasPairs($value, $out);
            }
        }
    }

    private function hydrateFromLastRun(): void
    {
        $run = ParserRun::query()
            ->forKey(self::PARSER_KEY)
            ->latest('id')
            ->first();

        if (! $run instanceof ParserRun) {
            $this->lastRunId = null;
            $this->lastParsedAt = null;
            return;
        }

        $this->lastRunId = (int) $run->id;
        $this->lastParsedAt = $run->created_at?->format('d.m.Y H:i:s');
        $this->stats = is_array($run->stats) ? $run->stats : null;
        $this->jsonResult = is_string($run->result_json) ? $run->result_json : null;
        $this->logOutput = is_string($run->log_output) ? $run->log_output : null;

        $params = is_array($run->params) ? $run->params : [];
        if (isset($params['deposit_url']) && is_string($params['deposit_url'])) {
            $this->depositUrl = $params['deposit_url'];
        }
        if (isset($params['parse_mode']) && is_string($params['parse_mode'])) {
            $this->parseMode = $params['parse_mode'];
        }
        if (isset($params['max_limit']) && is_numeric((string) $params['max_limit'])) {
            $this->maxLimit = (int) $params['max_limit'];
        }
        if (isset($params['file']) && is_string($params['file'])) {
            $this->catalogJsonFile = [$params['file']];
        }
    }

    /**
     * @param array<string, mixed> $params
     */
    private function persistRun(string $mode, array $params): void
    {
        $run = ParserRun::create([
            'parser_key' => self::PARSER_KEY,
            'mode' => $mode,
            'params' => $params,
            'stats' => $this->stats,
            'result_json' => $this->jsonResult,
            'log_output' => $this->logOutput,
        ]);

        $this->lastRunId = (int) $run->id;
        $this->lastParsedAt = $run->created_at?->format('d.m.Y H:i:s');
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
        $importer = new SravniDepositImporter();
        $results = [];
        $effectiveMode = $mode === 'upsert' ? 'update_existing' : $mode;

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $res = $importer->importOne($item, $effectiveMode);
            $results[] = [
                'status' => $res['status'] ?? 'skipped',
                'message' => $res['message'] ?? '—',
                'edit_url' => $res['edit_url'] ?? null,
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

