<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Cards\CardResource;
use App\Models\CardCategory;

class SectionSettingsPage extends BaseSectionSettingsPage
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'section-settings-cards';

    protected static ?string $title = 'Настройки раздела «Карты»';

    protected static ?string $navigationLabel = 'Настройки раздела «Карты»';

    protected static function sectionType(): string
    {
        return 'cards';
    }

    protected static function categoryClass(): string
    {
        return CardCategory::class;
    }

    protected static function resourceListUrl(): string
    {
        return CardResource::getUrl('index');
    }

    protected static function sectionLabel(): string
    {
        return 'Карты';
    }
}
