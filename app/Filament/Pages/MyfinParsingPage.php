<?php

namespace App\Filament\Pages;

use App\Models\Bank;
use App\Services\Parsers\Myfin\MyfinBankParser;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MyfinParsingPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Myfin — банки';

    protected static ?string $title = 'Парсинг банков (Myfin)';

    protected static ?string $slug = 'myfin-parsing';

    protected static string|\UnitEnum|null $navigationGroup = 'Парсинг';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.myfin-parsing';

    public ?string $logOutput = null;

    public ?string $jsonResult = null;

    public ?int $parserProgressPercent = 0;

    public ?string $banksLimit = null;

    public ?string $catalogHtml = null;

    /** @var array<int, array<string, mixed>> */
    public array $parsedBanks = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('')
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('banksLimit')
                            ->label('Количество банков для парсинга')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->placeholder('Пусто — парсить все банки'),
                        Textarea::make('catalogHtml')
                            ->label('HTML каталога Myfin')
                            ->rows(12)
                            ->placeholder('Необязательно. Если сервер не может открыть ru.myfin.by, вставьте сюда HTML страницы https://ru.myfin.by/banki')
                            ->helperText('Если поле заполнено, парсер возьмёт данные из него и не будет ходить в сеть.'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('run')
                ->label('Запустить парсинг')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->action(function (): void {
                    $this->logOutput = '';
                    $this->jsonResult = null;
                    $this->parserProgressPercent = 0;
                    $this->parsedBanks = [];

                    $limit = null;
                    if ($this->banksLimit !== null && $this->banksLimit !== '') {
                        $limit = (int) $this->banksLimit;
                        if ($limit < 1) {
                            $limit = null;
                        }
                    }

                    $parser = app(MyfinBankParser::class);
                    $parser->setLogCallback(function (string $message): void {
                        $this->logOutput = ($this->logOutput ?? '') . $message . "\n";
                    });
                    $parser->setProgressCallback(function (int $percent): void {
                        $this->parserProgressPercent = $percent;
                    });

                    $banks = $parser->parse($limit, $this->catalogHtml);
                    $this->parsedBanks = $banks;
                    $this->logOutput = trim($this->logOutput ?? '');
                    $this->jsonResult = json_encode($banks, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                    $this->parserProgressPercent = 100;

                    Notification::make()
                        ->title('Парсинг завершён')
                        ->body('Найдено банков: ' . count($banks))
                        ->success()
                        ->send();
                }),
        ];
    }

    public function importToDatabase(): void
    {
        $this->importBanksWithClientLogos($this->parsedBanks);
    }

    /**
     * @param  array<int, array<string, mixed>>  $banksPayload
     */
    public function importBanksWithClientLogos(array $banksPayload): void
    {
        if ($banksPayload === []) {
            Notification::make()
                ->title('Нет данных для импорта')
                ->warning()
                ->send();

            return;
        }

        $existingNames = Bank::query()
            ->pluck('name')
            ->mapWithKeys(fn (string $name): array => [$this->normalizeBankName($name) => true])
            ->all();

        $imported = 0;
        $skipped = 0;

        foreach ($banksPayload as $bankData) {
            $name = trim((string) ($bankData['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $normalizedName = $this->normalizeBankName($name);
            if (isset($existingNames[$normalizedName])) {
                $skipped++;
                $this->appendLog('Пропущено т.к найдено совпадение: ' . $name);

                continue;
            }

            $slug = Str::slug($name);
            $logoPath = $this->storeClientLogo(
                is_string($bankData['logo_data'] ?? null) ? (string) $bankData['logo_data'] : '',
                $slug,
            );

            if ($logoPath === null) {
                $logoPath = $this->downloadLogo(
                    is_string($bankData['logo'] ?? null) ? (string) $bankData['logo'] : '',
                    $slug,
                );
            }

            $logoValue = $logoPath ?: (filled($bankData['logo'] ?? null) ? (string) $bankData['logo'] : null);

            if ($logoPath === null && filled($logoValue)) {
                $this->appendLog('Логотип сохранён как внешний URL: ' . $logoValue);
            }

            Bank::create([
                'name' => $name,
                'slug' => $slug,
                'website' => filled($bankData['site'] ?? null) ? (string) $bankData['site'] : null,
                'phone' => $bankData['phones'][0] ?? null,
                'head_office' => filled($bankData['head_office'] ?? null) ? (string) $bankData['head_office'] : null,
                'license_number' => filled($bankData['registration_number'] ?? null) ? (string) $bankData['registration_number'] : null,
                'license_date' => $this->normalizeLicenseDate($bankData['registration_date'] ?? null),
                'logo' => $logoValue,
                'description' => null,
            ]);

            $existingNames[$normalizedName] = true;
            $imported++;
            $this->appendLog('Импортировано: ' . $name);
        }

        Notification::make()
            ->title('Импорт завершён')
            ->body("Добавлено: {$imported}. Пропущено: {$skipped}.")
            ->success()
            ->send();
    }

    private function appendLog(string $message): void
    {
        $current = trim($this->logOutput ?? '');
        $this->logOutput = trim($current . "\n" . $message);
    }

    private function normalizeBankName(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $name) ?? $name));
    }

    private function normalizeLicenseDate(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $value, $matches)) {
            return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
        }

        return null;
    }

    private function downloadLogo(string $logoUrl, string $slug): ?string
    {
        $logoUrl = trim($logoUrl);
        if ($logoUrl === '' || $slug === '') {
            return null;
        }

        $content = $this->fetchBinary($logoUrl);
        if ($content === null || $content === '') {
            $this->appendLog('Не удалось скачать логотип: ' . $logoUrl);

            return null;
        }

        $extension = $this->detectLogoExtension($logoUrl, $content);
        $relativePath = 'banks/' . $slug . '.' . $extension;

        Storage::disk('public')->put($relativePath, $content);
        $this->appendLog('Логотип сохранён: ' . $relativePath);

        return $relativePath;
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
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36',
                'Accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8',
                'Referer: https://ru.myfin.by/',
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

    private function storeClientLogo(string $dataUrl, string $slug): ?string
    {
        $dataUrl = trim($dataUrl);
        if ($dataUrl === '' || $slug === '') {
            return null;
        }

        if (! preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.+)$/', $dataUrl, $matches)) {
            $this->appendLog('Некорректный формат клиентского логотипа для: ' . $slug);

            return null;
        }

        $binary = base64_decode($matches[2], true);
        if (! is_string($binary) || $binary === '') {
            $this->appendLog('Не удалось декодировать логотип для: ' . $slug);

            return null;
        }

        $extension = $this->extensionFromMime($matches[1], $binary);
        $relativePath = 'banks/' . $slug . '.' . $extension;

        Storage::disk('public')->put($relativePath, $binary);
        $this->appendLog('Логотип сохранён из браузера: ' . $relativePath);

        return $relativePath;
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

    private function extensionFromMime(string $mimeType, string $content): string
    {
        return match (strtolower($mimeType)) {
            'image/svg+xml' => 'svg',
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => str_starts_with(ltrim($content), '<svg') ? 'svg' : 'png',
        };
    }
}
