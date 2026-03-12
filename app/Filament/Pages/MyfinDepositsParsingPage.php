<?php

namespace App\Filament\Pages;

use App\Services\Parsers\Myfin\MyfinDepositParser;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MyfinDepositsParsingPage extends Page implements HasForms
{
    use InteractsWithForms;

    /** Категории каталога Myfin вклады: value = URL, label = название */
    public const CATALOG_CATEGORIES = [
        'https://ru.myfin.by/vklady' => 'Все',
        'https://ru.myfin.by/vklady/v-dollarah' => 'В долларах',
        'https://ru.myfin.by/vklady/v-evro' => 'В евро',
        'https://ru.myfin.by/vklady/pensioneram' => 'Пенсионерам',
        'https://ru.myfin.by/vklady/pod-vysokii-procent' => 'Под высокий процент',
        'https://ru.myfin.by/vklady/vygodnye' => 'Выгодные',
    ];

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationLabel = 'Myfin — вклады';

    protected static ?string $title = 'Парсинг вкладов (Myfin)';

    protected static ?string $slug = 'myfin-deposits-parsing';

    protected static string|\UnitEnum|null $navigationGroup = 'Парсинг';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.myfin-deposits-parsing';

    public ?string $logOutput = null;

    public ?string $jsonResult = null;

    public ?int $parserProgressPercent = 0;

    public ?string $depositsLimit = null;

    public ?string $catalogHtml = null;

    /** URL выбранной категории каталога (пустая строка = «Все») */
    public ?string $catalogCategoryUrl = null;

    /** @var array<int, array<string, mixed>> */
    public array $parsedDeposits = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('')
            ->components([
                Section::make()
                    ->schema([
                        Select::make('catalogCategoryUrl')
                            ->label('Категория каталога')
                            ->options(self::CATALOG_CATEGORIES)
                            ->default('https://ru.myfin.by/vklady')
                            ->helperText('Парсинг только с выбранной категории. «Все» — каталог «Вклады» целиком.'),
                        TextInput::make('depositsLimit')
                            ->label('Количество вкладов для парсинга')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->placeholder('Пусто — парсить все вклады'),
                        Textarea::make('catalogHtml')
                            ->label('HTML каталога Myfin')
                            ->rows(12)
                            ->placeholder('Необязательно. Если сервер не может открыть ru.myfin.by, вставьте сюда HTML страницы https://ru.myfin.by/vklady')
                            ->helperText('Если поле заполнено, список вкладов будет взят из этого HTML. Для дозаполнения ставок, сроков, сумм и условий парсер всё равно попробует открыть детальные страницы вкладов.'),
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
                    $this->parsedDeposits = [];

                    $limit = null;
                    if ($this->depositsLimit !== null && $this->depositsLimit !== '') {
                        $limit = (int) $this->depositsLimit;
                        if ($limit < 1) {
                            $limit = null;
                        }
                    }

                    $categoryUrl = $this->catalogCategoryUrl !== null && $this->catalogCategoryUrl !== ''
                        ? $this->catalogCategoryUrl
                        : 'https://ru.myfin.by/vklady';
                    $categoryName = self::CATALOG_CATEGORIES[$categoryUrl] ?? 'Все';

                    $parser = app(MyfinDepositParser::class);
                    $parser->setLogCallback(function (string $message): void {
                        $this->logOutput = ($this->logOutput ?? '') . $message . "\n";
                    });
                    $parser->setProgressCallback(function (int $percent): void {
                        $this->parserProgressPercent = $percent;
                    });

                    $deposits = $parser->parse($limit, $this->catalogHtml, $categoryUrl, $categoryName);
                    $this->parsedDeposits = $deposits;
                    $this->logOutput = trim($this->logOutput ?? '');
                    $this->jsonResult = json_encode($deposits, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                    $this->parserProgressPercent = 100;

                    Notification::make()
                        ->title('Парсинг завершён')
                        ->body('Найдено вкладов: ' . count($deposits))
                        ->success()
                        ->send();
                }),
        ];
    }
}
