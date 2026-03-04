<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Deposits\DepositResource;
use App\Models\DepositCategory;

class SectionSettingsDepositsPage extends BaseSectionSettingsPage
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'section-settings-deposits';

    protected static ?string $title = 'Настройки раздела «Вклады»';

    protected static ?string $navigationLabel = 'Настройки раздела «Вклады»';

    protected static function sectionType(): string
    {
        return 'deposits';
    }

    protected static function categoryClass(): string
    {
        return DepositCategory::class;
    }

    protected static function resourceListUrl(): string
    {
        return DepositResource::getUrl('index');
    }

    protected static function sectionLabel(): string
    {
        return 'Вклады';
    }
}
