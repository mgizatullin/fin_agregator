<?php

namespace App\Filament\Pages;

use App\Models\Bank;
use App\Models\Credit;
use App\Services\Parsers\Myfin\MyfinCreditParser;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class MyfinCreditsParsingPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Myfin — кредиты';

    protected static ?string $title = 'Парсинг кредитов (Myfin)';

    protected static ?string $slug = 'myfin-credits-parsing';

    protected static string|\UnitEnum|null $navigationGroup = 'Парсинг';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.myfin-credits-parsing';

    public ?string $logOutput = null;

    public ?string $jsonResult = null;

    public ?int $parserProgressPercent = 0;

    public ?string $creditsLimit = null;

    public ?string $catalogHtml = null;

    /** @var array<int, array<string, mixed>> */
    public array $parsedCredits = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('')
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('creditsLimit')
                            ->label('Количество кредитов для парсинга')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->placeholder('Пусто — парсить все кредиты'),
                        Textarea::make('catalogHtml')
                            ->label('HTML каталога Myfin')
                            ->rows(12)
                            ->placeholder('Необязательно. Если сервер не может открыть ru.myfin.by, вставьте сюда HTML страницы https://ru.myfin.by/kredity')
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
                    $this->parsedCredits = [];

                    $limit = null;
                    if ($this->creditsLimit !== null && $this->creditsLimit !== '') {
                        $limit = (int) $this->creditsLimit;
                        if ($limit < 1) {
                            $limit = null;
                        }
                    }

                    $parser = app(MyfinCreditParser::class);
                    $parser->setLogCallback(function (string $message): void {
                        $this->logOutput = ($this->logOutput ?? '') . $message . "\n";
                    });
                    $parser->setProgressCallback(function (int $percent): void {
                        $this->parserProgressPercent = $percent;
                    });

                    $credits = $parser->parse($limit, $this->catalogHtml);
                    $this->parsedCredits = $credits;
                    $this->logOutput = trim($this->logOutput ?? '');
                    $this->jsonResult = json_encode($credits, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                    $this->parserProgressPercent = 100;

                    Notification::make()
                        ->title('Парсинг завершён')
                        ->body('Найдено кредитов: ' . count($credits))
                        ->success()
                        ->send();
                }),
        ];
    }

    public function importToDatabase(): void
    {
        if ($this->parsedCredits === []) {
            Notification::make()
                ->title('Нет данных для импорта')
                ->warning()
                ->send();

            return;
        }

        $banksByName = Bank::query()
            ->get(['id', 'name'])
            ->mapWithKeys(fn (Bank $bank): array => [$this->normalizeName($bank->name) => $bank])
            ->all();

        $existingCreditKeys = Credit::query()
            ->with('bank:id,name')
            ->get(['id', 'bank_id', 'name'])
            ->mapWithKeys(function (Credit $credit): array {
                $bankName = $credit->bank?->name;
                if (! is_string($bankName) || trim($bankName) === '' || ! is_string($credit->name) || trim($credit->name) === '') {
                    return [];
                }

                return [$this->makeCreditDuplicateKey($bankName, $credit->name) => true];
            })
            ->all();

        $imported = 0;
        $skipped = 0;

        foreach ($this->parsedCredits as $creditData) {
            $name = trim((string) ($creditData['name'] ?? ''));
            $bankName = trim((string) ($creditData['bank'] ?? ''));

            if ($name === '') {
                continue;
            }

            $bank = $banksByName[$this->normalizeName($bankName)] ?? null;
            if (! $bank instanceof Bank) {
                $skipped++;
                $this->appendLog('Пропущено т.к банк не найден для связи: ' . $bankName . ' / ' . $name);

                continue;
            }

            $duplicateKey = $this->makeCreditDuplicateKey($bank->name, $name);
            if (isset($existingCreditKeys[$duplicateKey])) {
                $skipped++;
                $this->appendLog('Пропущено т.к найдено совпадение в рамках банка: ' . $bank->name . ' / ' . $name);

                continue;
            }

            Credit::create([
                'bank_id' => $bank->id,
                'name' => $name,
                'slug' => filled($creditData['slug'] ?? null) ? (string) $creditData['slug'] : Str::slug($name),
                'rate' => $creditData['rate'] ?? null,
                'psk' => $creditData['psk'] ?? null,
                'max_amount' => $creditData['max_amount'] ?? null,
                'term_months' => $creditData['term_months'] ?? null,
                'income_proof_required' => (bool) ($creditData['income_proof_required'] ?? false),
                'age_min' => $creditData['age_min'] ?? null,
                'age_max' => $creditData['age_max'] ?? null,
                'decision' => filled($creditData['decision'] ?? null) ? (string) $creditData['decision'] : null,
                'receive_method' => filled($creditData['receive_method'] ?? null) ? (string) $creditData['receive_method'] : null,
                'payment_type' => filled($creditData['payment_type'] ?? null) ? (string) $creditData['payment_type'] : null,
                'penalty' => filled($creditData['penalty'] ?? null) ? (string) $creditData['penalty'] : null,
                'no_collateral' => (bool) ($creditData['no_collateral'] ?? false),
                'no_guarantors' => (bool) ($creditData['no_guarantors'] ?? false),
                'description' => null,
                'is_active' => true,
            ]);

            $existingCreditKeys[$duplicateKey] = true;
            $imported++;
            $this->appendLog('Импортировано: ' . $name . ' -> банк #' . $bank->id . ' (' . $bank->name . ')');
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

    private function normalizeName(string $value): string
    {
        $value = str_replace(['Ё', 'ё'], ['Е', 'е'], $value);
        $value = str_replace(['«', '»', '"'], '', $value);

        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value) ?? $value));
    }

    private function makeCreditDuplicateKey(string $bankName, string $creditName): string
    {
        return $this->normalizeName($bankName) . '|' . $this->normalizeName($creditName);
    }
}
