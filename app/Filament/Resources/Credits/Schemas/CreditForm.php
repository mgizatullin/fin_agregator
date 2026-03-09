<?php

namespace App\Filament\Resources\Credits\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

class CreditForm
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

                TextInput::make('psk')
                    ->label('ПСК')
                    ->numeric()
                    ->step('0.01'),

                TextInput::make('max_amount')
                    ->label('Макс. сумма')
                    ->numeric()
                    ->step('0.01'),

                TextInput::make('term_months')
                    ->label('Срок (мес.)')
                    ->numeric(),

                Toggle::make('income_proof_required')
                    ->label('Подтверждение дохода')
                    ->default(false)
                    ->required(),

                TextInput::make('age_min')
                    ->label('Возраст от')
                    ->numeric(),

                TextInput::make('age_max')
                    ->label('Возраст до')
                    ->numeric(),

                TextInput::make('decision')
                    ->label('Решение')
                    ->maxLength(255),

                TextInput::make('receive_method')
                    ->label('Способ получения')
                    ->maxLength(255),

                TextInput::make('payment_type')
                    ->label('Тип выплат')
                    ->maxLength(255),

                Textarea::make('penalty')
                    ->label('Штраф')
                    ->rows(4)
                    ->columnSpanFull(),

                Toggle::make('no_collateral')
                    ->label('Без залога')
                    ->default(false)
                    ->required(),

                Toggle::make('no_guarantors')
                    ->label('Без поручителей')
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
