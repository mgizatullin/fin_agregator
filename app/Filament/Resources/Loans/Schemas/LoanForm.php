<?php

namespace App\Filament\Resources\Loans\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
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

                Textarea::make('description')
                    ->label('Описание')
                    ->columnSpanFull(),

                Select::make('categories')
                    ->label('Категории')
                    ->relationship('categories', 'title')
                    ->multiple()
                    ->preload()
                    ->searchable(),

                TextInput::make('max_amount')
                    ->label('Макс. сумма')
                    ->numeric()
                    ->step('0.01'),

                TextInput::make('term_days')
                    ->label('Срок (дней)')
                    ->numeric(),

                TextInput::make('term_no_interest')
                    ->label('Срок без процентов (дней)')
                    ->numeric(),

                TextInput::make('psk')
                    ->label('ПСК')
                    ->numeric()
                    ->step('0.01'),

                TextInput::make('rate')
                    ->label('Ставка')
                    ->numeric()
                    ->step('0.01'),

                TextInput::make('website')
                    ->label('Сайт')
                    ->url()
                    ->maxLength(255),

                TextInput::make('rating')
                    ->label('Рейтинг')
                    ->numeric()
                    ->step('0.01'),

                Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true)
                    ->required(),
            ]);
    }
}
