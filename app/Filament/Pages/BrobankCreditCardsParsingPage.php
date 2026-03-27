<?php

namespace App\Filament\Pages;

use App\Models\Bank;
use App\Models\Card;
use App\Models\CardCategory;
use App\Services\Parsers\Brobank\BrobankCreditCardParser;
use App\Support\CardData;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrobankCreditCardsParsingPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'brobank.ru — кредитные карты';

    protected static ?string $title = 'Парсинг кредитных карт (brobank.ru)';

    protected static ?string $slug = 'brobank-credit-cards-parsing';

    protected static string|\UnitEnum|null $navigationGroup = 'Парсинг';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.brobank-credit-cards-parsing';

    public ?string $logOutput = null;

    public ?string $jsonResult = null;

    public ?int $parserProgressPercent = 0;

    public ?string $cardsLimit = null;

    public ?string $catalogHtml = null;

    /** @var array<int, array<string, mixed>> */
    public array $parsedCards = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('')
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('cardsLimit')
                            ->label('Количество кредитов для парсинга')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->placeholder('Пусто — парсить все кредитные карты'),
                        Textarea::make('catalogHtml')
                            ->label('HTML каталога')
                            ->rows(12)
                            ->placeholder('Необязательно. Если сервер не может открыть brobank.ru, вставьте сюда HTML страницы https://brobank.ru/kreditnye-karty/')
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
                    $this->parsedCards = [];

                    $limit = null;
                    if ($this->cardsLimit !== null && $this->cardsLimit !== '') {
                        $limit = (int) $this->cardsLimit;
                        if ($limit < 1) {
                            $limit = null;
                        }
                    }

                    $parser = app(BrobankCreditCardParser::class);
                    $parser->setLogCallback(function (string $message): void {
                        $this->logOutput = ($this->logOutput ?? '').$message."\n";
                    });
                    $parser->setProgressCallback(function (int $percent): void {
                        $this->parserProgressPercent = $percent;
                    });

                    $cards = $parser->parse($limit, $this->catalogHtml);
                    $this->parsedCards = $cards;
                    $this->logOutput = trim($this->logOutput ?? '');
                    $this->jsonResult = json_encode($cards, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                    $this->parserProgressPercent = 100;

                    Notification::make()
                        ->title('Парсинг завершён')
                        ->body('Найдено карт: '.count($cards))
                        ->success()
                        ->send();
                }),
        ];
    }

    public function importToDatabase(): void
    {
        $requiredColumns = [
            'atm_withdrawal_text',
            'conditions_items',
            'rates_items',
            'cashback_details_items',
        ];

        $missingColumns = array_values(array_filter(
            $requiredColumns,
            fn (string $column): bool => ! SchemaFacade::hasColumn('cards', $column),
        ));

        if ($missingColumns !== []) {
            Notification::make()
                ->title('Нужно применить миграции')
                ->body('В таблице cards не хватает колонок: '.implode(', ', $missingColumns).'. Выполните php artisan migrate.')
                ->danger()
                ->send();

            return;
        }

        if ($this->parsedCards === []) {
            Notification::make()
                ->title('Нет данных для импорта')
                ->warning()
                ->send();

            return;
        }

        $banks = Bank::query()->get(['id', 'name']);
        $categories = CardCategory::query()->get(['id', 'title', 'slug']);
        $existingCards = Card::query()
            ->with('bank:id,name')
            ->get(['id', 'bank_id', 'name'])
            ->keyBy(function (Card $card): string {
                return $this->makeCardDuplicateKey($card->bank?->name ?? '', $card->name ?? '');
            });

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $skippedItems = [];

        foreach ($this->parsedCards as $cardData) {
            $name = trim((string) ($cardData['name'] ?? ''));
            $bankName = trim((string) ($cardData['bank'] ?? ''));

            if ($name === '' || $bankName === '') {
                $skipped++;
                $this->appendLog('Пропущено: не заполнены название или банк');
                $skippedItems[] = trim(($bankName !== '' ? $bankName : 'Неизвестный банк').' - '.($name !== '' ? $name : 'Без названия'));

                continue;
            }

            $bank = $this->resolveBank($bankName, $banks);
            if (! $bank instanceof Bank) {
                $skipped++;
                $skippedLabel = $bankName.' - '.$name;
                $this->appendLog('Пропущено т.к. банк не найден для связи: '.$skippedLabel);
                $skippedItems[] = $skippedLabel;

                continue;
            }

            $duplicateKey = $this->makeCardDuplicateKey($bank->name, $name);
            $card = $existingCards[$duplicateKey] ?? new Card;
            $isNew = ! $card->exists;
            $storedImage = $this->storeCardImage(
                $this->nullableString($cardData['image'] ?? null),
                $bank->name,
                $name,
                $card->image,
            );

            $card->fill([
                'bank_id' => $bank->id,
                'name' => $name,
                'slug' => $isNew ? Str::slug($name) : ($card->slug ?: Str::slug($name)),
                'credit_limit' => $this->normalizeDecimalValue($cardData['credit_limit'] ?? null),
                'psk_text' => $this->nullableString($cardData['psk_text'] ?? null),
                'grace_period' => $this->extractFirstInteger($cardData['grace_period_text'] ?? null),
                'grace_period_text' => $this->nullableString($cardData['grace_period_text'] ?? null),
                'annual_fee_text' => $this->nullableString($cardData['annual_fee_text'] ?? null),
                'cashback' => $this->nullableString($cardData['cashback_text'] ?? null),
                'cashback_text' => $this->nullableString($cardData['cashback_text'] ?? null),
                'decision_text' => $this->nullableString($cardData['decision_text'] ?? null),
                'atm_withdrawal_text' => $this->nullableString(
                    CardData::findValueByParameter(
                        CardData::extractAccordionItems($cardData['accordion'] ?? null, 'Проценты'),
                        ['Комиссия за снятие наличных', 'Снятие в банкомате']
                    )
                ),
                'card_type' => $this->nullableString(
                    CardData::findValueByParameter(
                        CardData::extractAccordionItems($cardData['accordion'] ?? null, 'Условия'),
                        'Тип карты'
                    ) ?? ($cardData['card_type'] ?? null)
                ),
                'image' => $storedImage,
                'conditions_text' => $this->formatAccordionSection($cardData['accordion'] ?? null, 'Условия'),
                'rates_text' => $this->formatAccordionSection($cardData['accordion'] ?? null, 'Проценты'),
                'cashback_details_text' => $this->formatAccordionSection($cardData['accordion'] ?? null, ['Кешбэк', 'Кэшбэк']),
                'conditions_items' => CardData::extractAccordionItems($cardData['accordion'] ?? null, 'Условия'),
                'rates_items' => CardData::extractAccordionItems($cardData['accordion'] ?? null, 'Проценты'),
                'cashback_details_items' => CardData::extractAccordionItems($cardData['accordion'] ?? null, ['Кешбэк', 'Кэшбэк']),
                'is_active' => true,
            ]);

            $card->save();

            $this->syncCardCategories($card, $cardData['tags'] ?? [], $categories);

            $existingCards[$duplicateKey] = $card->fresh(['bank']);

            if ($isNew) {
                $imported++;
                $this->appendLog('Импортировано: '.$name.' -> банк #'.$bank->id.' ('.$bank->name.')');
            } else {
                $updated++;
                $this->appendLog('Обновлено: '.$name.' -> банк #'.$bank->id.' ('.$bank->name.')');
            }
        }

        if ($skippedItems !== []) {
            $this->appendLog('Пропущенные позиции:');
            foreach ($skippedItems as $item) {
                $this->appendLog($item);
            }
        }

        Notification::make()
            ->title('Импорт завершён')
            ->body(
                $skippedItems === []
                    ? "Добавлено: {$imported}. Обновлено: {$updated}. Пропущено: {$skipped}."
                    : "Добавлено: {$imported}. Обновлено: {$updated}. Пропущено: {$skipped}. Список пропусков добавлен в лог."
            )
            ->success()
            ->send();
    }

    private function appendLog(string $message): void
    {
        $current = trim($this->logOutput ?? '');
        $this->logOutput = trim($current."\n".$message);
    }

    private function resolveBank(string $bankName, Collection $banks): ?Bank
    {
        $normalizedBankName = $this->normalizeName($bankName);
        $bankNameWithoutParentheses = $this->normalizeName((string) preg_replace('/\s*\([^)]*\)/u', '', $bankName));

        foreach ($banks as $bank) {
            $candidate = $this->normalizeName($bank->name);
            if ($candidate === $normalizedBankName || $candidate === $bankNameWithoutParentheses) {
                return $bank;
            }
        }

        foreach ($banks as $bank) {
            $candidate = $this->normalizeName($bank->name);
            if (
                $candidate !== ''
                && (
                    str_contains($normalizedBankName, $candidate)
                    || str_contains($candidate, $bankNameWithoutParentheses)
                )
            ) {
                return $bank;
            }
        }

        return null;
    }

    private function syncCardCategories(Card $card, mixed $tags, Collection $categories): void
    {
        if (! is_array($tags)) {
            $card->categories()->sync([]);

            return;
        }

        $matchedIds = [];

        foreach ($tags as $tag) {
            if (! is_string($tag) || trim($tag) === '') {
                continue;
            }

            $normalizedTag = $this->normalizeName($tag);
            $tagSlug = Str::slug($tag);

            /** @var CardCategory|null $category */
            $category = $categories->first(function (CardCategory $category) use ($normalizedTag, $tagSlug): bool {
                return $this->normalizeName($category->title) === $normalizedTag || $category->slug === $tagSlug;
            });

            if ($category instanceof CardCategory) {
                $matchedIds[] = $category->id;

                continue;
            }

            $this->appendLog('Категория не найдена, тег пропущен: '.$tag);
        }

        $card->categories()->sync(array_values(array_unique($matchedIds)));
    }

    private function formatAccordionSection(mixed $accordion, string|array $sectionTitles): ?string
    {
        if (! is_array($accordion)) {
            return null;
        }

        $section = null;
        foreach ((array) $sectionTitles as $sectionTitle) {
            $candidate = $accordion[$sectionTitle] ?? null;
            if (is_array($candidate) && $candidate !== []) {
                $section = $candidate;

                break;
            }
        }

        if (! is_array($section) || $section === []) {
            return null;
        }

        $lines = [];

        foreach ($section as $label => $value) {
            $label = trim((string) $label);
            $value = trim((string) $value);

            if ($label === '' || $value === '') {
                continue;
            }

            $lines[] = $label.': '.$value;
        }

        return $lines === [] ? null : implode("\n", $lines);
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function extractFirstInteger(mixed $value): ?int
    {
        if (! is_scalar($value)) {
            return null;
        }

        if (preg_match('/\d+/u', (string) $value, $matches) !== 1) {
            return null;
        }

        return (int) $matches[0];
    }

    private function normalizeDecimalValue(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function normalizeName(string $value): string
    {
        $value = str_replace(['Ё', 'ё'], ['Е', 'е'], $value);
        $value = str_replace(['«', '»', '"'], '', $value);

        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value) ?? $value));
    }

    private function makeCardDuplicateKey(string $bankName, string $cardName): string
    {
        return $this->normalizeName($bankName).'|'.$this->normalizeName($cardName);
    }

    private function storeCardImage(?string $imageUrl, string $bankName, string $cardName, mixed $existingImage = null): ?string
    {
        $existingImage = $this->nullableString($existingImage);

        if ($existingImage !== null && ! str_starts_with($existingImage, 'http')) {
            return $existingImage;
        }

        if ($imageUrl === null) {
            return $existingImage;
        }

        if (! str_starts_with($imageUrl, 'http')) {
            return $imageUrl;
        }

        $content = $this->fetchBinary($imageUrl);
        if ($content === null) {
            $this->appendLog('Не удалось скачать изображение карты: '.$bankName.' - '.$cardName);

            return $existingImage ?: $imageUrl;
        }

        $fileName = Str::slug($bankName.'-'.$cardName);
        $extension = $this->detectImageExtension($imageUrl, $content);
        $relativePath = 'cards/'.($fileName !== '' ? $fileName : 'card-'.Str::random(8)).'.'.$extension;

        Storage::disk('public')->put($relativePath, $content);
        $this->appendLog('Изображение карты сохранено: '.$relativePath);

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
                'Referer: https://brobank.ru/',
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

    private function detectImageExtension(string $url, string $content): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = is_string($path) ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : '';

        if (in_array($extension, ['svg', 'png', 'jpg', 'jpeg', 'webp', 'gif'], true)) {
            return $extension === 'jpeg' ? 'jpg' : $extension;
        }

        $trimmed = ltrim($content);

        if (str_starts_with($trimmed, '<svg')) {
            return 'svg';
        }

        return 'png';
    }
}
