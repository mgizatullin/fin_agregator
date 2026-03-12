<?php

namespace App\Filament\Resources\Reviews\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('reviewable_label')
                    ->label('Продукт')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('title')
                    ->label('Заголовок')
                    ->maxLength(255),
                Select::make('service')
                    ->label('Услуга')
                    ->options(fn (): array => \App\Models\Review::query()->distinct()->whereNotNull('service')->where('service', '!=', '')->orderBy('service')->pluck('service', 'service')->toArray())
                    ->searchable(),
                Select::make('bank_id')
                    ->label('Банк')
                    ->relationship('bank', 'name')
                    ->searchable()
                    ->preload(),
                Textarea::make('body')
                    ->label('Текст отзыва')
                    ->rows(5)
                    ->columnSpanFull(),
                TextInput::make('rating')
                    ->label('Оценка')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)
                    ->required(),
                TextInput::make('name')
                    ->label('Имя')
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('Телефон')
                    ->tel()
                    ->maxLength(255),
                Toggle::make('is_published')
                    ->label('Опубликован')
                    ->columnSpanFull(),
            ]);
    }
}
