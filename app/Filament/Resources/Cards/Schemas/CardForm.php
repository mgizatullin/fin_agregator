<?php

namespace App\Filament\Resources\Cards\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('cardTabs')
                    ->tabs([
                        Tab::make('Основное')
                            ->schema([
                                Section::make()
                                    ->schema([
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

                                        TextInput::make('credit_limit')
                                            ->label('Кредитный лимит')
                                            ->numeric()
                                            ->step('0.01'),

                                        TextInput::make('psk_text')
                                            ->label('ПСК')
                                            ->maxLength(255),

                                        TextInput::make('grace_period')
                                            ->label('Льготный период (дней)')
                                            ->numeric(),

                                        TextInput::make('cashback')
                                            ->label('Кэшбэк')
                                            ->maxLength(255),

                                        TextInput::make('annual_fee_text')
                                            ->label('Стоимость обслуживания')
                                            ->maxLength(255),

                                        TextInput::make('atm_withdrawal_text')
                                            ->label('Снятие в банкомате')
                                            ->maxLength(255),

                                        TextInput::make('card_type')
                                            ->label('Тип карты')
                                            ->maxLength(255),

                                        TextInput::make('decision_text')
                                            ->label('Срок рассмотрения')
                                            ->maxLength(255),

                                        FileUpload::make('image')
                                            ->label('Изображение карты')
                                            ->image()
                                            ->directory('cards')
                                            ->disk('public')
                                            ->maxSize(4096),

                                        Select::make('categories')
                                            ->label('Категории')
                                            ->relationship('categories', 'title')
                                            ->multiple()
                                            ->preload()
                                            ->searchable(),

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
                                    ])
                                    ->columns(2),
                            ]),

                        Tab::make('Характеристики')
                            ->schema([
                                Section::make('Подробные характеристики')
                                    ->schema([
                                        Repeater::make('conditions_items')
                                            ->label('Условия')
                                            ->schema(self::detailItemSchema())
                                            ->addActionLabel('Добавить параметр')
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->defaultItems(0)
                                            ->columnSpanFull(),

                                        Repeater::make('rates_items')
                                            ->label('Проценты')
                                            ->schema(self::detailItemSchema())
                                            ->addActionLabel('Добавить параметр')
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->defaultItems(0)
                                            ->columnSpanFull(),

                                        Repeater::make('cashback_details_items')
                                            ->label('Кешбэк (подробно)')
                                            ->schema(self::detailItemSchema())
                                            ->addActionLabel('Добавить параметр')
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->defaultItems(0)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    private static function detailItemSchema(): array
    {
        return [
            TextInput::make('parameter')
                ->label('Параметр')
                ->required()
                ->maxLength(255),
            TextInput::make('value')
                ->label('Значение')
                ->required()
                ->maxLength(255),
        ];
    }
}
