<?php

namespace App\Filament\Resources\Deposits\Schemas;

use App\Models\Deposit;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

class DepositForm
{
    public const DEPOSIT_TYPES = [
        'Накопительный счет' => 'Накопительный счет',
        'Классический' => 'Классический',
        'Пенсионный' => 'Пенсионный',
        'Инвестиционный' => 'Инвестиционный',
        'Для клиентов банка' => 'Для клиентов банка',
    ];

    public const CURRENCY_CODES = [
        'RUB' => 'RUB (рубль)',
        'USD' => 'USD (доллар)',
        'EUR' => 'EUR (евро)',
        'CNY' => 'CNY (юань)',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('bank_id')
                    ->label('Банк')
                    ->relationship('bank', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),

                TextInput::make('slug')
                    ->label('URL-код')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Grid::make(2)->schema([
                    TextInput::make('deposit_type')
                        ->label('Тип вклада')
                        ->maxLength(255)
                        ->datalist(function (): array {
                            $base = array_keys(self::DEPOSIT_TYPES);
                            $fromDb = Deposit::query()
                                ->whereNotNull('deposit_type')
                                ->where('deposit_type', '!=', '')
                                ->select('deposit_type')
                                ->distinct()
                                ->orderBy('deposit_type')
                                ->pluck('deposit_type')
                                ->filter()
                                ->values()
                                ->all();

                            return array_values(array_unique(array_merge($base, $fromDb)));
                        }),

                    Select::make('categories')
                        ->label('Категории')
                        ->relationship('categories', 'title')
                        ->multiple()
                        ->preload()
                        ->searchable(),
                ]),

                Grid::make(3)->schema([
                    Toggle::make('capitalization')
                        ->label('Капитализация')
                        ->default(false)
                        ->required(),

                    Toggle::make('online_opening')
                        ->label('Открытие онлайн')
                        ->default(false)
                        ->required(),

                    Toggle::make('monthly_interest_payment')
                        ->label('Выплата процентов ежемесячно')
                        ->default(false)
                        ->required(),

                    Toggle::make('partial_withdrawal')
                        ->label('Частичное снятие')
                        ->default(false)
                        ->required(),

                    Toggle::make('replenishment')
                        ->label('Пополнение')
                        ->default(false)
                        ->required(),

                    Toggle::make('early_termination')
                        ->label('Досрочное расторжение')
                        ->default(false)
                        ->required(),

                    Toggle::make('auto_prolongation')
                        ->label('Автопролонгация')
                        ->default(false)
                        ->required(),

                    Toggle::make('insurance')
                        ->label('Страхование')
                        ->default(false)
                        ->required(),
                ])->columnSpanFull(),

                Section::make('Условия по валютам')
                    ->description('Ставки: валюта → диапазон суммы → срок → ставка. Сроки сортируются по возрастанию; верхняя граница срока вычисляется автоматически (до следующего срока − 1).')
                    ->schema([
                        Repeater::make('currencies')
                            ->itemLabel(fn (array $state): ?string => self::CURRENCY_CODES[$state['currency_code'] ?? ''] ?? ($state['currency_code'] ?? null))
                            ->collapsible()
                            ->addActionLabel('Добавить валюту')
                            ->schema([
                                Select::make('currency_code')
                                    ->label('Валюта')
                                    ->options(self::CURRENCY_CODES)
                                    ->required(),
                                Repeater::make('amount_ranges')
                                    ->itemLabel(fn (array $state): string => self::formatAmountRangeLabel($state))
                                    ->collapsible()
                                    ->collapsed()
                                    ->addActionLabel('Добавить диапазон сумм')
                                    ->schema([
                                        TextInput::make('amount_min')
                                            ->label('Сумма от')
                                            ->numeric()
                                            ->step('0.01')
                                            ->minValue(0),
                                        TextInput::make('amount_max')
                                            ->label('Сумма до (пусто = без ограничения)')
                                            ->numeric()
                                            ->step('0.01')
                                            ->minValue(0),
                                        Repeater::make('terms')
                                            ->itemLabel(fn (array $state): string => sprintf('%s дн. → %s%%', $state['term_days'] ?? '?', $state['rate'] ?? '?'))
                                            ->collapsible()
                                            ->collapsed()
                                            ->addActionLabel('Добавить срок')
                                            ->schema([
                                                TextInput::make('term_days')
                                                    ->label('Срок (дн.)')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required(),
                                                TextInput::make('rate')
                                                    ->label('Ставка (%)')
                                                    ->numeric()
                                                    ->step('0.001')
                                                    ->required(),
                                                Toggle::make('is_active')
                                                    ->label('Активна')
                                                    ->default(true),
                                            ])
                                            ->columns(3)
                                            ->defaultItems(0),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(0),
                            ])
                            ->columns(1)
                            ->defaultItems(0),
                    ])
                    ->columnSpanFull(),

                View::make('filament.deposits.deposit-rates-table-preview')
                    ->visible(fn (?\Illuminate\Database\Eloquent\Model $record): bool => $record !== null),

                RichEditor::make('description')
                    ->label('Описание')
                    ->columnSpanFull()
                    ->json(false)
                    ->extraInputAttributes(['style' => 'min-height: 300px'])
                    ->toolbarButtons([
                        ['bold', 'italic', 'link'],
                        ['h2', 'h3'],
                        ['bulletList', 'orderedList'],
                    ]),

                Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function formatAmountRangeLabel(array $state): string
    {
        $min = $state['amount_min'] ?? null;
        $max = $state['amount_max'] ?? null;
        if ($min !== null && $min !== '' && $max !== null && $max !== '') {
            return sprintf('%s – %s', number_format((float) $min, 0, '.', ' '), number_format((float) $max, 0, '.', ' '));
        }
        if ($min !== null && $min !== '') {
            return 'от ' . number_format((float) $min, 0, '.', ' ');
        }
        return 'Диапазон сумм';
    }
}
