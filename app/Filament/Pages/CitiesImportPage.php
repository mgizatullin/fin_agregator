<?php

namespace App\Filament\Pages;

use App\Models\City;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class CitiesImportPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Импорт городов';

    protected static ?string $title = 'Импорт городов (CSV)';

    protected static ?string $slug = 'cities-import';

    protected static string|\UnitEnum|null $navigationGroup = 'Парсинг';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.cities-import';

    public ?string $csvFile = null;

    public ?int $rowsCount = null;

    public ?int $validRowsCount = null;

    public ?int $importedRowsCount = null;

    public array $previewRows = [];

    public array $validationIssues = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('')
            ->components([
                Section::make()
                    ->schema([
                        FileUpload::make('csvFile')
                            ->label('CSV-файл')
                            ->disk('public')
                            ->directory('imports/cities')
                            ->acceptedFileTypes(['text/csv', 'application/csv', '.csv', 'text/plain'])
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
            $check = $this->validateCityRow($row, $line);

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
            $check = $this->validateCityRow($row, $line);

            if (! $check['valid']) {
                $issues = array_merge($issues, $check['issues']);

                continue;
            }

            $cityData = $check['data'];

            City::create([
                'name' => $cityData['name'],
                'name_genitive' => $cityData['name_genitive'],
                'name_prepositional' => $cityData['name_prepositional'],
                'region' => $cityData['region'],
                'population' => $cityData['population'],
                'is_active' => true,
            ]);

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

    protected function loadCsvRows(): array
    {
        if (blank($this->csvFile)) {
            return [false, 'Файл CSV не загружен.'];
        }

        if (! Storage::disk('public')->exists($this->csvFile)) {
            return [false, 'Файл не найден в хранилище. Попробуйте загрузить заново.'];
        }

        $path = Storage::disk('public')->path($this->csvFile);

        if (! is_file($path) || ! is_readable($path)) {
            return [false, 'Не удалось открыть файл CSV для чтения.'];
        }

        $handle = fopen($path, 'r');
        if (! $handle) {
            return [false, 'Не удалось открыть файл CSV для чтения.'];
        }

        $firstLine = fgetcsv($handle, 0, ';');
        if ($firstLine === false) {
            fclose($handle);

            return [false, 'Не удалось прочитать CSV.'];
        }

        $firstLower = array_map(fn ($item) => mb_strtolower(trim((string) $item)), $firstLine);
        $hasHeader = isset($firstLower[0], $firstLower[1])
            && $firstLower[0] === 'регион'
            && $firstLower[1] === 'город';

        $headers = ['регион', 'город'];
        $rows = [];

        if (! $hasHeader) {
            $rows[] = [
                'регион' => isset($firstLine[0]) ? trim((string) $firstLine[0]) : '',
                'город' => isset($firstLine[1]) ? trim((string) $firstLine[1]) : '',
            ];
        }

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
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

    protected function validateCityRow(array $row, int $line): array
    {
        $issues = [];

        $region = trim((string) ($row['регион'] ?? ''));
        $name = trim((string) ($row['город'] ?? ''));

        if ($region === '') {
            $issues[] = "Строка {$line}: Не указан регион.";
        }

        if ($name === '') {
            $issues[] = "Строка {$line}: Не указано название города.";
        }

        if ($region !== '' && $name !== '') {
            $exists = City::where('name', $name)->where('region', $region)->exists();
            if ($exists) {
                $issues[] = "Строка {$line}: Город '{$name}' в регионе '{$region}' уже существует.";
            }
        }

        $nameGenitive = '';
        $namePrepositional = '';
        $population = 0;

        if ($name !== '') {
            // Generate genitive and prepositional using Morpher API
            $morphResult = $this->getMorpherData($name);
            if ($morphResult) {
                $nameGenitive = $morphResult['genitive'] ?? $name;
                $namePrepositional = $morphResult['prepositional'] ?? $name;
            } else {
                $issues[] = "Строка {$line}: Не удалось получить склонения для '{$name}'.";
            }

            // Get population from Wikipedia
            $popResult = $this->getPopulationFromWikipedia($name, $region);
            if ($popResult !== null) {
                $population = $popResult;
            } else {
                $issues[] = "Строка {$line}: Не удалось найти население для '{$name}' в '{$region}'.";
            }
        }

        $valid = count($issues) === 0;

        return [
            'valid' => $valid,
            'issues' => $issues,
            'data' => [
                'name' => $name,
                'name_genitive' => $nameGenitive,
                'name_prepositional' => $namePrepositional,
                'region' => $region,
                'population' => $population,
            ],
        ];
    }

    protected function getMorpherData(string $name): ?array
    {
        try {
            $response = Http::timeout(10)->get('https://ws3.morpher.ru/russian/declension', [
                's' => $name,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'genitive' => $data['Р'] ?? null, // Родительный
                    'prepositional' => $data['П'] ?? null, // Предложный
                ];
            }
        } catch (\Exception $e) {
            // Log error if needed
        }

        return null;
    }

    protected function getPopulationFromWikipedia(string $name, string $region): ?int
    {
        try {
            // Try Russian Wikipedia
            $title = $name;
            if (str_contains($region, 'область') || str_contains($region, 'край') || str_contains($region, 'Республика')) {
                $title .= ' ('.$region.')';
            }

            $response = Http::timeout(10)->get('https://ru.wikipedia.org/api/rest_v1/page/summary/'.urlencode($title));

            if ($response->successful()) {
                $data = $response->json();
                $extract = $data['extract'] ?? '';

                // Simple regex to find population
                if (preg_match('/население.*?(\d{1,3}(?:[ \.,]\d{3})*)/ui', $extract, $matches)) {
                    $popStr = str_replace([' ', '.', ','], '', $matches[1]);

                    return (int) $popStr;
                }
            }

            // Fallback to English Wikipedia
            $response = Http::timeout(10)->get('https://en.wikipedia.org/api/rest_v1/page/summary/'.urlencode($name));

            if ($response->successful()) {
                $data = $response->json();
                $extract = $data['extract'] ?? '';

                if (preg_match('/population.*?(\d{1,3}(?:[ \.,]\d{3})*)/ui', $extract, $matches)) {
                    $popStr = str_replace([' ', '.', ','], '', $matches[1]);

                    return (int) $popStr;
                }
            }
        } catch (\Exception $e) {
            // Log error if needed
        }

        return null;
    }
}
