<?php

namespace App\Filament\Resources\Banks\Schemas;

use App\Filament\Resources\Banks\RelationManagers\BranchesRelationManager;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Illuminate\Support\Str;

class BankForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Редактирование банка')
                    ->tabs([
                        Tab::make('Основная информация')
                            ->schema([
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

                                TextInput::make('website')
                                    ->label('Сайт')
                                    ->url()
                                    ->maxLength(255),

                                TextInput::make('phone')
                                    ->label('Телефон')
                                    ->tel()
                                    ->maxLength(255),

                                TextInput::make('head_office')
                                    ->label('Головной офис')
                                    ->maxLength(255),

                                TextInput::make('license_number')
                                    ->label('Номер лицензии')
                                    ->maxLength(255),

                                DatePicker::make('license_date')
                                    ->label('Дата лицензии'),

                                RichEditor::make('description')
                                    ->label('Описание')
                                    ->columnSpanFull()
                                    ->extraInputAttributes(['style' => 'min-height: 300px'])
                                    ->toolbarButtons([
                                        ['bold', 'italic', 'link'],
                                        ['h2', 'h3'],
                                        ['bulletList', 'orderedList'],
                                    ]),
                                FileUpload::make('logo')
                                    ->label('Логотип')
                                    ->image()
                                    ->disk('public')
                                    ->visibility('public')
                                    ->directory('banks')
                                    ->imagePreviewHeight(120)
                                    ->maxSize(2048),

                                FileUpload::make('logo_square')
                                    ->label('Логотип (квадрат)')
                                    ->image()
                                    ->disk('public')
                                    ->visibility('public')
                                    ->directory('banks')
                                    ->imagePreviewHeight(120)
                                    ->maxSize(2048),

                                Select::make('categories')
                                    ->label('Категории')
                                    ->relationship('categories', 'title')
                                    ->multiple()
                                    ->preload()
                                    ->searchable(),

                                TextInput::make('rating')
                                    ->label('Рейтинг')
                                    ->numeric()
                                    ->step('0.01'),

                                Toggle::make('is_active')
                                    ->label('Активен')
                                    ->default(true)
                                    ->required(),
                            ])
                            ->columns(2),

                        Tab::make('Отделения')
                            ->schema([
                                Livewire::make(
                                    BranchesRelationManager::class,
                                    fn (Component $livewire): array => [
                                        'ownerRecord' => $livewire->getRecord(),
                                        'pageClass' => $livewire::class,
                                    ],
                                )->columnSpanFull(),
                            ])
                            ->visible(fn (?Model $record): bool => filled($record)),

                        Tab::make('SEO настройки')
                            ->schema([
                                TextInput::make('seo_title')
                                    ->label('SEO Title')
                                    ->maxLength(255),

                                Textarea::make('seo_description')
                                    ->label('SEO Description')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
