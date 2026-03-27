<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Models\Service;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('ServiceTabs')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Основное')
                            ->schema([
                                TextInput::make('type')
                                    ->label('Тип сервиса')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->formatStateUsing(fn (?string $state) => match ($state) {
                                        Service::TYPE_CREDIT_CALCULATOR => 'Калькулятор кредитов',
                                        Service::TYPE_DEPOSIT_CALCULATOR => 'Калькулятор вкладов',
                                        default => $state ?? '',
                                    }),
                                TextInput::make('title')
                                    ->label('Заголовок')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('slug')
                                    ->label('URL')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->helperText('Сегмент пути без «/services/», например: kreditnyy-kalkulyator'),
                                Toggle::make('is_active')
                                    ->label('Активен')
                                    ->default(true),
                            ]),
                        Tab::make('SEO настройки')
                            ->schema([
                                TextInput::make('seo_title')
                                    ->label('SEO Title')
                                    ->maxLength(255),
                                Textarea::make('seo_description')
                                    ->label('SEO Description')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
