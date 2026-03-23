<?php

namespace App\Filament\Resources\Loans\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Str;

class LoanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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

                TextInput::make('company_name')
                    ->label('Компания')
                    ->required()
                    ->maxLength(255),

                FileUpload::make('logo')
                    ->label('Логотип')
                    ->image()
                    ->disk('public')
                    ->visibility('public')
                    ->directory('logos')
                    ->imagePreviewHeight(120)
                    ->maxSize(2048),

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
                Grid::make(2)->schema([
                    TextInput::make('min_amount')
                        ->label('Сумма (от)')
                        ->numeric()
                        ->step('0.01'),
                    TextInput::make('max_amount')
                        ->label('Сумма (до)')
                        ->numeric()
                        ->step('0.01'),
                ]),

                Grid::make(2)->schema([
                    TextInput::make('term_days_min')
                        ->label('Срок (дней) от')
                        ->numeric(),
                    TextInput::make('term_days')
                        ->label('Срок (дней) до')
                        ->numeric(),
                ]),

                TextInput::make('term_no_interest')
                    ->label('Срок без процентов (дней)')
                    ->numeric(),

                Grid::make(2)->schema([
                    TextInput::make('psk_min')
                        ->label('ПСК от')
                        ->numeric()
                        ->step('0.01'),
                    TextInput::make('psk')
                        ->label('ПСК до')
                        ->numeric()
                        ->step('0.01'),
                ]),

                Grid::make(2)->schema([
                    TextInput::make('rate_min')
                        ->label('Ставка от')
                        ->numeric()
                        ->step('0.01'),
                    TextInput::make('rate')
                        ->label('Ставка до')
                        ->numeric()
                        ->step('0.01'),
                ]),

                TextInput::make('website')
                    ->label('Сайт')
                    ->url()
                    ->maxLength(255),

                Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true)
                    ->required(),

                Select::make('categories')
                    ->label('Категории')
                    ->relationship('categories', 'title')
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ]);
    }
}
