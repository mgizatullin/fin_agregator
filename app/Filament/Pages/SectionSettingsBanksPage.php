<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Banks\BankResource;
use App\Models\BankCategory;

class SectionSettingsBanksPage extends BaseSectionSettingsPage
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'section-settings-banks';

    protected static ?string $title = 'Настройки раздела «Банки»';

    protected static ?string $navigationLabel = 'Настройки раздела «Банки»';

    protected static function sectionType(): string
    {
        return 'banks';
    }

    protected static function categoryClass(): string
    {
        return BankCategory::class;
    }

    protected static function resourceListUrl(): string
    {
        return BankResource::getUrl('index');
    }

    protected static function sectionLabel(): string
    {
        return 'Банки';
    }
}
