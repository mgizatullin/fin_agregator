<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Credits\CreditResource;
use App\Models\CreditCategory;

class SectionSettingsCreditsPage extends BaseSectionSettingsPage
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'section-settings-credits';

    protected static ?string $title = 'Настройки раздела «Кредиты»';

    protected static ?string $navigationLabel = 'Настройки раздела «Кредиты»';

    protected static function sectionType(): string
    {
        return 'credits';
    }

    protected static function categoryClass(): string
    {
        return CreditCategory::class;
    }

    protected static function resourceListUrl(): string
    {
        return CreditResource::getUrl('index');
    }

    protected static function sectionLabel(): string
    {
        return 'Кредиты';
    }
}
