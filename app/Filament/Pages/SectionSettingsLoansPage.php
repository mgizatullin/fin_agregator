<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Loans\LoanResource;
use App\Models\LoanCategory;

class SectionSettingsLoansPage extends BaseSectionSettingsPage
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'section-settings-loans';

    protected static ?string $title = 'Настройки раздела «Займы»';

    protected static ?string $navigationLabel = 'Настройки раздела «Займы»';

    protected static function sectionType(): string
    {
        return 'loans';
    }

    protected static function categoryClass(): string
    {
        return LoanCategory::class;
    }

    protected static function resourceListUrl(): string
    {
        return LoanResource::getUrl('index');
    }

    protected static function sectionLabel(): string
    {
        return 'Займы';
    }
}
