<?php

namespace App\Filament\Resources\Articles\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->tabs([
                        Tab::make('Основная информация')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Заголовок')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state)))
                                    ->columnSpan(12),

                                TextInput::make('slug')
                                    ->label('URL')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->columnSpan(12),

                                Select::make('category_id')
                                    ->label('Категория')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->columnSpan(12),

                                TextInput::make('author')
                                    ->label('Автор')
                                    ->maxLength(255)
                                    ->columnSpan(12),

                                FileUpload::make('image')
                                    ->label('Изображение')
                                    ->image()
                                    ->disk('public')
                                    ->directory('blog/articles')
                                    ->maxSize(2048)
                                    ->imagePreviewHeight(200)
                                    ->columnSpanFull(),

                                Toggle::make('is_published')
                                    ->label('Опубликовано')
                                    ->default(false)
                                    ->required()
                                    ->columnSpan(12),

                                DatePicker::make('published_at')
                                    ->label('Дата публикации')
                                    ->native(false)
                                    ->displayFormat('d.m.Y')
                                    ->format('Y-m-d')
                                    ->nullable()
                                    ->dehydrateStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('Y-m-d') . ' 00:00:00' : null)
                                    ->columnSpan(3),
                            ])
                            ->columns(12),

                        Tab::make('Контент')
                            ->schema([
                                Textarea::make('excerpt')
                                    ->label('Краткое описание')
                                    ->rows(4)
                                    ->columnSpanFull(),

                                RichEditor::make('content')
                                    ->label('Текст статьи')
                                    ->columnSpanFull()
                                    ->extraInputAttributes(['style' => 'min-height: 300px']),
                            ])
                            ->columns(1),

                        Tab::make('SEO настройки')
                            ->schema([
                                TextInput::make('seo_title')
                                    ->label('SEO Title')
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Textarea::make('seo_description')
                                    ->label('SEO Description')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])
                            ->columns(1),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
