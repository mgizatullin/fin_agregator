<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Loans\LoanResource;
use App\Models\Loan;
use App\Models\LoanCategory;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JsonLoansPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-code-bracket-square';

    protected static ?string $navigationLabel = 'JSON Займы';

    protected static ?string $title = 'JSON Займы';

    protected static ?string $slug = 'json-loans';

    protected static string|\UnitEnum|null $navigationGroup = 'Парсинг';

    protected static ?int $navigationSort = 11;

    protected string $view = 'filament.pages.json-loans-page';

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
                            ->label('JSON массив')
                            ->rows(18)
                            ->placeholder('[{"name":"Компания","title":"Название займа"}]')
                            ->helperText('Поддерживается массив JSON, один объект JSON или список объектов, вставленный без внешних квадратных скобок.'),
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

        [$categoryMap, $categoryTitlesById] = $this->buildLoanCategoryMap();
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

        $existingLoans = Loan::query()
            ->with('categories:id')
            ->get(['id', 'name', 'company_name', 'slug', 'logo'])
            ->keyBy(fn (Loan $loan): string => $this->makeLoanDuplicateKey($loan->company_name, $loan->name));

        $results = [];
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($this->recognizedItems as $item) {
            if (! ($item['ready'] ?? false)) {
                $results[] = [
                    'status' => 'skipped',
                    'message' => 'Пропущено: есть ошибки распознавания у записи #'.(($item['index'] ?? 0) + 1),
                    'edit_url' => null,
                ];
                $skipped++;

                continue;
            }

            $payload = $item['payload'];
            $lookup = $item['lookup'] ?? [];
            $key = $this->makeLoanDuplicateKey(
                (string) ($lookup['company_name'] ?? ''),
                (string) ($lookup['name'] ?? ''),
            );

            $loan = $existingLoans[$key] ?? null;
            $status = 'updated';

            if (! $loan instanceof Loan) {
                $loan = new Loan;
                $status = 'created';
                $loan->is_active = true;
            }

            $loan->fill($payload);

            if (($item['has_logo'] ?? false) && filled($item['logo_url'] ?? null)) {
                $logoPath = $this->downloadLogo(
                    (string) $item['logo_url'],
                    (string) ($item['logo_basename'] ?? $this->makeLogoBasename(
                        (string) ($lookup['company_name'] ?? ''),
                        (string) ($lookup['name'] ?? ''),
                    )),
                    $loan->logo,
                );
                if ($logoPath !== null) {
                    $loan->logo = $logoPath;
                }
            }

            if (array_key_exists('description', $payload)) {
                $loan->description = description_ensure_html($payload['description'] ?? '');
            }

            $loan->save();

            if (($item['has_categories'] ?? false) === true) {
                $categoryIds = array_values(array_unique(array_map('intval', $item['category_ids'] ?? [])));
                $loan->categories()->sync($categoryIds);
            }

            $existingLoans[$key] = $loan->fresh(['categories:id']) ?? $loan;

            if ($status === 'created') {
                $created++;
            } else {
                $updated++;
            }

            $results[] = [
                'status' => $status,
                'message' => ($status === 'created' ? 'Создано' : 'Обновлено').': '.$loan->name.' / '.$loan->company_name,
                'edit_url' => LoanResource::getUrl('edit', ['record' => $loan]),
            ];

            usleep(random_int(3_000_000, 5_000_000));
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
        $candidates = [];
        $trimmed = trim($raw);
        $candidates[] = $trimmed;
        $candidates[] = preg_replace('/,\s*([\]}])/u', '$1', $trimmed) ?? $trimmed;

        $wrapped = '['.trim($trimmed, ", \n\r\t").']';
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
                'logo_url' => null,
            ];
        }

        $name = trim((string) ($item['title'] ?? ''));
        $companyName = trim((string) ($item['name'] ?? ''));
        $hasDescription = array_key_exists('description', $item);
        $hasCategories = array_key_exists('categories', $item);
        $hasLogo = array_key_exists('logo', $item) && filled($item['logo']);

        if ($name === '') {
            $errors[] = 'Не заполнено поле title.';
        }

        if ($companyName === '') {
            $errors[] = 'Не заполнено поле name.';
        }

        $amountFrom = $this->parseDecimal($item['amount_from'] ?? null, 'amount_from', $errors);
        $amountTo = $this->parseDecimal($item['amount_to'] ?? null, 'amount_to', $errors);
        $noInterestTerm = $this->parseInteger($item['no_interest_term'] ?? null, 'no_interest_term', $errors);
        $termFrom = $this->parseInteger($item['term_from'] ?? null, 'term_from', $errors);
        $termTo = $this->parseInteger($item['term_to'] ?? null, 'term_to', $errors);
        $pskFrom = $this->parseDecimal($item['psk_from'] ?? null, 'psk_from', $errors);
        $pskTo = $this->parseDecimal($item['psk_to'] ?? null, 'psk_to', $errors);
        $rateFrom = $this->parseDecimal($item['rate_from'] ?? null, 'rate_from', $errors);
        $rateTo = $this->parseDecimal($item['rate_to'] ?? null, 'rate_to', $errors);

        if ($amountFrom !== null && $amountTo !== null && $amountFrom > $amountTo) {
            $errors[] = 'amount_from больше amount_to.';
        }

        if ($termFrom !== null && $termTo !== null && $termFrom > $termTo) {
            $errors[] = 'term_from больше term_to.';
        }

        if ($pskFrom !== null && $pskTo !== null && $pskFrom > $pskTo) {
            $errors[] = 'psk_from больше psk_to.';
        }

        if ($rateFrom !== null && $rateTo !== null && $rateFrom > $rateTo) {
            $errors[] = 'rate_from больше rate_to.';
        }

        $logoUrl = filled($item['logo'] ?? null) ? trim((string) $item['logo']) : null;
        if ($logoUrl !== null && ! filter_var($logoUrl, FILTER_VALIDATE_URL)) {
            $warnings[] = 'Поле logo не похоже на корректный URL.';
            $logoUrl = null;
        }

        $categoryNames = $item['categories'] ?? [];
        if (! is_array($categoryNames)) {
            $warnings[] = 'Поле categories должно быть массивом.';
            $categoryNames = [];
        }

        $categoryIds = [];
        $unknownCategories = [];
        foreach ($categoryNames as $categoryName) {
            if (! is_string($categoryName) || trim($categoryName) === '') {
                continue;
            }

            $normalized = $this->normalizeCategoryName($categoryName);
            $matched = $categoryMap[$normalized] ?? [];

            if ($matched === []) {
                $unknownCategories[] = trim($categoryName);
                $warnings[] = 'Категория не найдена: '.trim($categoryName);

                continue;
            }

            $categoryIds = array_merge($categoryIds, $matched);
        }

        $categoryIds = array_values(array_unique(array_map('intval', $categoryIds)));
        $matchedCategoryTitles = array_values(array_map(
            fn (int $id): string => $categoryTitlesById[$id] ?? ('#'.$id),
            $categoryIds,
        ));

        $payload = [
            'name' => $name,
            'company_name' => $companyName,
        ];

        if (array_key_exists('amount_from', $item) && $amountFrom !== null) {
            $payload['min_amount'] = $amountFrom;
        }
        if (array_key_exists('amount_to', $item) && $amountTo !== null) {
            $payload['max_amount'] = $amountTo;
        }
        if (array_key_exists('no_interest_term', $item) && $noInterestTerm !== null) {
            $payload['term_no_interest'] = $noInterestTerm;
        }
        if (array_key_exists('term_from', $item) && $termFrom !== null) {
            $payload['term_days_min'] = $termFrom;
        }
        if (array_key_exists('term_to', $item) && $termTo !== null) {
            $payload['term_days'] = $termTo;
        }
        if (array_key_exists('psk_from', $item) && $pskFrom !== null) {
            $payload['psk_min'] = $pskFrom;
        }
        if (array_key_exists('psk_to', $item) && $pskTo !== null) {
            $payload['psk'] = $pskTo;
        }
        if (array_key_exists('rate_from', $item) && $rateFrom !== null) {
            $payload['rate_min'] = $rateFrom;
        }
        if (array_key_exists('rate_to', $item) && $rateTo !== null) {
            $payload['rate'] = $rateTo;
        }
        if ($hasDescription && filled($item['description'])) {
            $payload['description'] = (string) $item['description'];
        }

        return [
            'index' => $index,
            'source_name' => $companyName !== '' ? $companyName.' / '.$name : $name,
            'ready' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
            'category_ids' => $categoryIds,
            'matched_categories' => $matchedCategoryTitles,
            'unknown_categories' => $unknownCategories,
            'lookup' => [
                'name' => $name,
                'company_name' => $companyName,
            ],
            'payload' => $payload,
            'logo_url' => $logoUrl,
            'logo_basename' => $this->makeLogoBasename($companyName, $name),
            'has_logo' => $hasLogo,
            'has_categories' => $hasCategories,
        ];
    }

    /**
     * @return array{0: array<string, array<int, int>>, 1: array<int, string>}
     */
    private function buildLoanCategoryMap(): array
    {
        $map = [];
        $titlesById = [];

        foreach (LoanCategory::query()->get(['id', 'title', 'h1_template']) as $category) {
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

    private function normalizeCategoryName(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['Ё', 'ё'], ['е', 'е'], $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
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

    private function makeLoanDuplicateKey(?string $companyName, ?string $name): string
    {
        return $this->normalizeText($companyName).'|'.$this->normalizeText($name);
    }

    private function normalizeText(?string $value): string
    {
        $value = (string) $value;
        $value = str_replace(['Ё', 'ё', '«', '»', '"'], ['Е', 'е', '', '', ''], $value);

        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value) ?? $value));
    }

    private function downloadLogo(string $logoUrl, string $basename, ?string $existingPath = null): ?string
    {
        $logoUrl = trim($logoUrl);
        $basename = trim($basename);

        if ($logoUrl === '' || $basename === '') {
            return null;
        }

        $content = $this->fetchBinary($logoUrl);
        if ($content === null || $content === '') {
            return null;
        }

        $extension = $this->detectLogoExtension($logoUrl, $content);
        $relativePath = 'logos/json-loans/'.$basename.'.'.$extension;

        if ($existingPath && $existingPath !== $relativePath && Storage::disk('public')->exists($existingPath)) {
            Storage::disk('public')->delete($existingPath);
        }

        Storage::disk('public')->put($relativePath, $content);

        return $relativePath;
    }

    private function makeLogoBasename(string $companyName, string $name): string
    {
        $basename = Str::slug(trim($companyName.' '.$name));

        return $basename !== '' ? $basename : 'loan-logo';
    }

    private function fetchBinary(string $url): ?string
    {
        $ch = curl_init();
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0',
                'Accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
            ],
        ]);

        $body = curl_exec($ch);
        $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($body === false || $errno !== 0 || $httpStatus < 200 || $httpStatus >= 400) {
            return null;
        }

        return is_string($body) ? $body : null;
    }

    private function detectLogoExtension(string $url, string $content): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = is_string($path) ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : '';

        if (in_array($extension, ['svg', 'png', 'jpg', 'jpeg', 'webp'], true)) {
            return $extension === 'jpeg' ? 'jpg' : $extension;
        }

        return str_starts_with(ltrim($content), '<svg') ? 'svg' : 'png';
    }
}
