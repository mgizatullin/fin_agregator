<?php

namespace App\Filament\Pages;

use App\Models\Bank;
use App\Models\Branch;
use App\Models\City;
use Filament\Actions\Action;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BranchesImportPage extends Page implements HasForms
{
    use InteractsWithForms;

    /**
     * Соответствие типовых русских заголовков выгрузок внутренним ключам (после mb_strtolower).
     */
    protected const CSV_HEADER_ALIASES = [
        'название' => 'bank',
        'наименование' => 'bank',
        'наименование банка' => 'bank',
        'название банка' => 'bank',
        'банк' => 'bank',
        'регион' => 'region',
        'область' => 'region',
        'город' => 'city',
        'адрес' => 'address',
        'телефон' => 'phone',
        'тел' => 'phone',
        'время работы' => 'working_hours',
        'режим работы' => 'working_hours',
        'график работы' => 'working_hours',
        'широта' => 'latitude',
        'долгота' => 'longitude',
    ];

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Импорт отделений';

    protected static ?string $title = 'Импорт отделений (CSV)';

    protected static ?string $slug = 'branches-import';

    protected static string|\UnitEnum|null $navigationGroup = 'Парсинг';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.branches-import';

    /**
     * Состояние формы (в т.ч. FileUpload) хранится под statePath `data`, как в типовых страницах Filament.
     *
     * @var array{csvFile?: array<string, string>|string|null}
     */
    public array $data = [
        'csvFile' => [],
    ];

    public ?int $rowsCount = null;

    public ?int $validRowsCount = null;

    public ?int $importedRowsCount = null;

    public array $previewRows = [];

    public array $validationIssues = [];

    /** @var array<string, City>|null */
    protected ?array $citiesByNormalizedKey = null;

    /** @var array<string, Collection<int, City>>|null */
    protected ?array $citiesByNormalizedName = null;

    /** @var array<string, Bank>|null */
    protected ?array $banksByNormalizedName = null;

    /** @var Collection<int, Bank>|null */
    protected ?Collection $banksForFuzzyMatch = null;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make()
                    ->schema([
                        FileUpload::make('csvFile')
                            ->label('CSV-файл')
                            ->disk('public')
                            ->directory('imports/branches')
                            ->acceptedFileTypes([
                                'text/csv',
                                'application/csv',
                                'text/plain',
                                'application/octet-stream',
                                'application/vnd.ms-excel',
                            ])
                            ->mimeTypeMap([
                                'csv' => 'text/csv',
                            ])
                            ->fetchFileInformation(false)
                            ->maxSize(10240)
                            ->required(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recognize')
                ->label('Распознать')
                ->icon('heroicon-o-eye')
                ->color('secondary')
                ->action('recognizeCsv'),

            Action::make('validate')
                ->label('Проверить')
                ->icon('heroicon-o-check-badge')
                ->color('warning')
                ->action('validateCsv'),

            Action::make('import')
                ->label('Импортировать')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->requiresConfirmation()
                ->action('importCsv'),
        ];
    }

    public function recognizeCsv(): void
    {
        $this->resetStatus();

        $result = $this->loadCsvRows();
        if ($result[0] === false) {
            Notification::make()
                ->title('Ошибка распознавания CSV')
                ->danger()
                ->body($result[1] ?? 'Не удалось прочитать файл.')
                ->send();

            return;
        }

        [$headers, $rows] = $result;

        $this->rowsCount = count($rows);
        $this->previewRows = array_slice($rows, 0, 8);

        Notification::make()
            ->title('CSV распознан')
            ->success()
            ->body('Найдено строк: '.$this->rowsCount)
            ->send();

        if (count($this->previewRows) === 0) {
            Notification::make()
                ->title('Файл пустой')
                ->warning()
                ->body('В файле нет данных после заголовка.')
                ->send();
        }
    }

    public function validateCsv(): void
    {
        $this->resetStatus();

        $result = $this->loadCsvRows();
        if ($result[0] === false) {
            Notification::make()
                ->title('Ошибка проверки CSV')
                ->danger()
                ->body($result[1] ?? 'Не удалось прочитать файл.')
                ->send();

            return;
        }

        [$headers, $rows] = $result;

        $this->rowsCount = count($rows);
        $this->previewRows = array_slice($rows, 0, 8);

        $issues = [];
        $validCount = 0;

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $check = $this->validateBranchRow($row, $line);

            if ($check['valid']) {
                $validCount++;
            }

            foreach ($check['issues'] as $issue) {
                $issues[] = $issue;
            }
        }

        $this->validationIssues = $issues;
        $this->validRowsCount = $validCount;

        $message = 'Проверка завершена (строк:'.$this->rowsCount.', допустимых:'.$this->validRowsCount.')';
        if (count($issues) > 0) {
            Notification::make()
                ->title('Обнаружено ошибок')
                ->danger()
                ->body($message)
                ->send();
        } else {
            Notification::make()
                ->title('Проверка успешно завершена')
                ->success()
                ->body($message)
                ->send();
        }
    }

    public function importCsv(): void
    {
        $this->resetStatus();

        $result = $this->loadCsvRows();
        if ($result[0] === false) {
            Notification::make()
                ->title('Ошибка импорта CSV')
                ->danger()
                ->body($result[1] ?? 'Не удалось прочитать файл.')
                ->send();

            return;
        }

        [$headers, $rows] = $result;

        $issues = [];
        $importCount = 0;

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $check = $this->validateBranchRow($row, $line);

            if (! $check['valid']) {
                $issues = array_merge($issues, $check['issues']);

                continue;
            }

            $branchData = $check['data'];

            // Создаем/обновляем отделение по ключу {bank+city+address}
            Branch::updateOrCreate(
                [
                    'bank_id' => $branchData['bank']->id,
                    'city_id' => $branchData['city']->id,
                    'address' => $branchData['address'],
                ],
                [
                    'region' => $branchData['region'],
                    'phone' => $branchData['phone'],
                    'working_hours' => $branchData['working_hours'],
                    'latitude' => $branchData['latitude'],
                    'longitude' => $branchData['longitude'],
                    'is_active' => true,
                ]
            );

            $importCount++;
        }

        $this->rowsCount = count($rows);
        $this->previewRows = array_slice($rows, 0, 8);
        $this->validationIssues = $issues;
        $this->importedRowsCount = $importCount;

        if (count($issues) > 0) {
            Notification::make()
                ->title('Импорт завершён с предупреждениями')
                ->warning()
                ->body('Импортировано: '.$importCount.'. Ошибок: '.count($issues).'.')
                ->send();
        } else {
            Notification::make()
                ->title('Импорт успешно завершён')
                ->success()
                ->body('Импортировано: '.$importCount.' строк.')
                ->send();
        }
    }

    protected function resetStatus(): void
    {
        $this->rowsCount = null;
        $this->validRowsCount = null;
        $this->importedRowsCount = null;
        $this->previewRows = [];
        $this->validationIssues = [];
    }

    protected function resolveUploadedCsvFilePath(): ?string
    {
        $state = $this->data['csvFile'] ?? null;

        if (blank($state)) {
            return null;
        }

        if (is_array($state)) {
            $first = Arr::first($state);

            return is_string($first) && $first !== '' ? $first : null;
        }

        return trim((string) $state);
    }

    /**
     * Сохраняет временные загрузки Livewire на диск до чтения CSV (иначе в state остаётся livewire-file:…).
     */
    protected function persistCsvFileUpload(): void
    {
        $schema = $this->getSchema('form');

        if ($schema === null) {
            return;
        }

        $component = $schema->getComponentByStatePath('data.csvFile', withAbsoluteStatePath: true);

        if ($component instanceof BaseFileUpload) {
            $component->saveUploadedFiles();
        }
    }

    protected function detectCsvDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return ';';
        }

        $firstLine = fgets($handle);
        fclose($handle);

        if ($firstLine === false || trim($firstLine) === '') {
            return ';';
        }

        $semicolonCount = substr_count($firstLine, ';');
        $commaCount = substr_count($firstLine, ',');

        return $commaCount > $semicolonCount ? ',' : ';';
    }

    protected function loadCsvRows(): array
    {
        $this->persistCsvFileUpload();

        $relativePath = $this->resolveUploadedCsvFilePath();

        if ($relativePath === null) {
            return [false, 'Файл CSV не загружен.'];
        }

        if (! Storage::disk('public')->exists($relativePath)) {
            return [false, 'Файл не найден в хранилище. Попробуйте загрузить заново.'];
        }

        $path = Storage::disk('public')->path($relativePath);

        if (! is_file($path) || ! is_readable($path)) {
            return [false, 'Не удалось открыть файл CSV для чтения.'];
        }

        $delimiter = $this->detectCsvDelimiter($path);

        $handle = fopen($path, 'r');
        if (! $handle) {
            return [false, 'Не удалось открыть файл CSV для чтения.'];
        }

        $headers = fgetcsv($handle, 0, $delimiter);
        if ($headers === false) {
            fclose($handle);

            return [false, 'Не удалось прочитать заголовок CSV.'];
        }

        $headers = array_map(function ($item) {
            $item = str_replace("\xEF\xBB\xBF", '', (string) $item);

            return mb_strtolower(trim($item));
        }, $headers);

        $headers = array_map(fn (string $h): string => self::CSV_HEADER_ALIASES[$h] ?? $h, $headers);

        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $items = [];
            foreach ($headers as $columnIndex => $columnName) {
                $items[$columnName] = isset($row[$columnIndex]) ? trim((string) $row[$columnIndex]) : '';
            }

            $rows[] = $items;
        }

        fclose($handle);

        return [$headers, $rows];
    }

    protected function validateBranchRow(array $row, int $line): array
    {
        $issues = [];

        $bankIdentifier = $row['bank_id'] ?? $row['bank'] ?? $row['bank_name'] ?? $row['bank_slug'] ?? null;
        $cityName = trim((string) ($row['city'] ?? $row['city_name'] ?? ''));
        $regionName = trim((string) ($row['region'] ?? ''));
        $address = trim((string) ($row['address'] ?? $row['street'] ?? ''));
        $phone = trim((string) ($row['phone'] ?? ''));
        $workingHours = trim((string) ($row['working_hours'] ?? $row['work_hours'] ?? $row['schedule'] ?? ''));
        $latitudeRaw = trim((string) ($row['latitude'] ?? $row['lat'] ?? $row['lon'] ?? ''));
        $longitudeRaw = trim((string) ($row['longitude'] ?? $row['lng'] ?? $row['lon'] ?? ''));
        $latitude = $this->parseFloat($latitudeRaw);
        $longitude = $this->parseFloat($longitudeRaw);

        if ($bankIdentifier === null || $bankIdentifier === '') {
            $issues[] = "Строка {$line}: Не указан банк (колонка «Название» / bank / bank_name / bank_slug).";
        }

        if ($cityName === '') {
            $issues[] = "Строка {$line}: Не указан город (колонка «Город» / city).";
        }

        if ($regionName === '') {
            $issues[] = "Строка {$line}: Не указан регион (колонка «Регион» / region).";
        }

        if ($address === '') {
            $issues[] = "Строка {$line}: Не указан адрес (колонка «Адрес» / address).";
        }

        $bank = null;
        if ($bankIdentifier !== null && $bankIdentifier !== '') {
            $bank = $this->findBank($bankIdentifier);
            if ($bank === null) {
                $issues[] = "Строка {$line}: Банк '{$bankIdentifier}' не найден.";
            }
        }

        $city = null;
        if ($cityName !== '' && $regionName !== '') {
            $this->warmCityLookupCaches();
            $lookupKey = $this->normalizePlaceKey($cityName).'|'.$this->normalizePlaceKey($regionName);
            $city = $this->citiesByNormalizedKey[$lookupKey] ?? null;

            if ($city === null) {
                $nameKey = $this->normalizePlaceKey($cityName);
                $citiesWithName = $this->citiesByNormalizedName[$nameKey] ?? collect();

                if ($citiesWithName->count() === 1) {
                    $cityByName = $citiesWithName->first();
                    $issues[] = "Строка {$line}: Город '{$cityName}' найден, но регион не совпадает (в файле '{$regionName}', в базе '{$cityByName->region}').";
                } elseif ($citiesWithName->count() > 1) {
                    $issues[] = "Строка {$line}: Город '{$cityName}' встречается в нескольких регионах — уточните регион (в файле указано '{$regionName}').";
                } else {
                    $issues[] = "Строка {$line}: Город '{$cityName}' с регионом '{$regionName}' не найден.";
                }
            }
        }

        if ($latitudeRaw !== '' && $latitude === null) {
            $issues[] = "Строка {$line}: Некорректные координаты широты '{$latitudeRaw}'.";
        }

        if ($longitudeRaw !== '' && $longitude === null) {
            $issues[] = "Строка {$line}: Некорректные координаты долготы '{$longitudeRaw}'.";
        }

        $valid = count($issues) === 0;

        return [
            'valid' => $valid,
            'issues' => $issues,
            'data' => [
                'bank' => $bank,
                'city' => $city,
                'region' => $city?->region ?? $regionName,
                'address' => $address,
                'phone' => $phone,
                'working_hours' => $workingHours,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ],
        ];
    }

    protected function findBank(string $identifier): ?Bank
    {
        $clean = trim($identifier);
        if ($clean === '') {
            return null;
        }

        if (is_numeric($clean)) {
            return Bank::find((int) $clean);
        }

        $this->warmBankLookupCaches();

        $key = $this->normalizeBankIdentifier($clean);
        if ($key === '') {
            return null;
        }

        if (isset($this->banksByNormalizedName[$key])) {
            return $this->banksByNormalizedName[$key];
        }

        $slugified = Str::slug($clean);
        if ($slugified !== '') {
            $bySlug = $this->banksForFuzzyMatch->firstWhere('slug', $slugified);
            if ($bySlug !== null) {
                return $bySlug;
            }
        }

        $best = null;
        $bestPercent = 0.0;

        foreach ($this->banksForFuzzyMatch as $bank) {
            $nk = $this->normalizeBankIdentifier($bank->name);
            if ($nk === '') {
                continue;
            }

            similar_text($key, $nk, $percent);
            if ($percent > $bestPercent) {
                $bestPercent = $percent;
                $best = $bank;
            }
        }

        if ($best !== null && $bestPercent >= 86.0) {
            return $best;
        }

        foreach ($this->banksForFuzzyMatch as $bank) {
            $nk = $this->normalizeBankIdentifier($bank->name);
            if ($nk === '') {
                continue;
            }

            $lenKey = mb_strlen($key);
            $lenNk = mb_strlen($nk);
            if (max($lenKey, $lenNk) < 5) {
                continue;
            }

            if (! str_contains($nk, $key) && ! str_contains($key, $nk)) {
                continue;
            }

            similar_text($key, $nk, $percent);
            if ($percent >= 72.0 && ($best === null || $percent > $bestPercent)) {
                $bestPercent = $percent;
                $best = $bank;
            }
        }

        return ($best !== null && $bestPercent >= 72.0) ? $best : null;
    }

    protected function warmBankLookupCaches(): void
    {
        if ($this->banksByNormalizedName !== null) {
            return;
        }

        $this->banksForFuzzyMatch = Bank::query()->get(['id', 'name', 'slug']);
        $map = [];

        foreach ($this->banksForFuzzyMatch as $bank) {
            $nk = $this->normalizeBankIdentifier($bank->name);
            if ($nk !== '' && ! isset($map[$nk])) {
                $map[$nk] = $bank;
            }
        }

        $this->banksByNormalizedName = $map;
    }

    /**
     * Приведение названия банка из выгрузки к виду для сравнения с БД:
     * латиница, похожая на кириллицу (часто в Excel), дефисы, хвосты «, банкомат», ё/е.
     */
    protected function normalizeBankIdentifier(string $value): string
    {
        $value = str_replace(["\xc2\xa0", "\xe2\x80\xaf"], ' ', $value);
        $value = trim($value);
        $value = preg_replace('/\s*,\s*банкомат\s*$/ui', '', $value) ?? $value;
        $value = preg_replace('/\s*\(\s*банкомат\s*\)\s*$/ui', '', $value) ?? $value;
        $value = preg_replace('/\s+банкомат\s*$/ui', '', $value) ?? $value;
        $value = str_replace(['-', '–', '—'], ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = $this->replaceLatinLookalikesWithCyrillic($value);
        $value = mb_strtolower($value, 'UTF-8');
        $value = str_replace('ё', 'е', $value);
        $value = preg_replace('/^банк\s+/u', '', $value) ?? $value;

        return trim($value);
    }

    protected function replaceLatinLookalikesWithCyrillic(string $value): string
    {
        static $map = [
            'A' => 'А', 'B' => 'Б', 'C' => 'С', 'E' => 'Е', 'H' => 'Н', 'I' => 'И',
            'K' => 'К', 'M' => 'М', 'O' => 'О', 'P' => 'Р', 'T' => 'Т', 'X' => 'Х', 'Y' => 'У',
            'a' => 'а', 'b' => 'б', 'c' => 'с', 'e' => 'е', 'h' => 'н', 'i' => 'и',
            'k' => 'к', 'm' => 'м', 'o' => 'о', 'p' => 'р', 't' => 'т', 'x' => 'х', 'y' => 'у',
        ];

        return strtr($value, $map);
    }

    protected function parseFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        /**
         * Allow comma as decimal separator.
         * Use float cast for final value.
         */
        $normalized = str_replace([',', ' '], ['.', ''], trim((string) $value));

        if (is_numeric($normalized)) {
            return (float) $normalized;
        }

        return null;
    }

    /**
     * Сравнение названий регионов/городов: без учёта регистра, ё/е, лишних пробелов и неразрывного пробела.
     */
    protected function normalizePlaceKey(string $value): string
    {
        $value = str_replace(["\xc2\xa0", "\xe2\x80\xaf"], ' ', $value);
        $value = trim($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = mb_strtolower($value, 'UTF-8');
        $value = str_replace('ё', 'е', $value);

        return $value;
    }

    protected function warmCityLookupCaches(): void
    {
        if ($this->citiesByNormalizedKey !== null) {
            return;
        }

        $byKey = [];
        $byName = [];

        foreach (City::query()->cursor() as $city) {
            $nameKey = $this->normalizePlaceKey($city->name);
            $fullKey = $nameKey.'|'.$this->normalizePlaceKey($city->region);
            $byKey[$fullKey] = $city;

            if (! isset($byName[$nameKey])) {
                $byName[$nameKey] = collect();
            }
            $byName[$nameKey]->push($city);
        }

        $this->citiesByNormalizedKey = $byKey;
        $this->citiesByNormalizedName = $byName;
    }
}
