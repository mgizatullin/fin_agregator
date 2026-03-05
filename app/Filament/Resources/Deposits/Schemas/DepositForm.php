<?php

namespace App\Filament\Resources\Deposits\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

class DepositForm
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

                TextInput::make('rate')
                    ->label('Ставка')
                    ->numeric()
                    ->step('0.01'),

                TextInput::make('term_months')
                    ->label('Срок (мес.)')
                    ->numeric(),

                TextInput::make('min_amount')
                    ->label('Мин. сумма')
                    ->numeric()
                    ->step('0.01'),

                Toggle::make('replenishment')
                    ->label('Пополнение')
                    ->default(false)
                    ->required(),

                Toggle::make('partial_withdrawal')
                    ->label('Частичное снятие')
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
