<?php

namespace App\Filament\Resources\Cards\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

class CardForm
{
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

                TextInput::make('credit_limit')
                    ->label('Кредитный лимит')
                    ->numeric()
                    ->step('0.01'),

                TextInput::make('grace_period')
                    ->label('Льготный период (дней)')
                    ->numeric(),

                TextInput::make('annual_fee')
                    ->label('Годовое обслуживание')
                    ->numeric()
                    ->step('0.01'),

                TextInput::make('psk')
                    ->label('ПСК')
                    ->numeric()
                    ->step('0.01'),

                TextInput::make('rate')
                    ->label('Ставка')
                    ->numeric()
                    ->step('0.01'),

                TextInput::make('cashback')
                    ->label('Кэшбэк')
                    ->maxLength(255),

                TextInput::make('issue_cost')
                    ->label('Стоимость выпуска')
                    ->numeric()
                    ->step('0.01'),

                Toggle::make('atm_withdrawal')
                    ->label('Снятие в банкомате')
                    ->default(false)
                    ->required(),

                TextInput::make('card_type')
                    ->label('Тип карты')
                    ->maxLength(255),

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
                Select::make('categories')
                    ->label('Категории')
                    ->relationship('categories', 'title')
                    ->multiple()
                    ->preload()
                    ->searchable(),

                Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true)
                    ->required(),
            ]);
    }
}
