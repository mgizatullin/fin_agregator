<?php

namespace App\Providers;

use App\Filament\Auth\Http\Responses\LoginResponse;
use App\Models\Article;
use App\Models\BankCategory;
use App\Models\CreditCategory;
use App\Models\DepositCategory;
use App\Models\LoanCategory;
use App\Models\SiteSettings;
use App\Observers\ArticleObserver;
use App\Services\CbrRatesService;
use Carbon\Carbon;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LoginResponseContract::class, LoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Config::set('livewire.payload.max_size', 5 * 1024 * 1024);

        Carbon::setLocale('ru');
        Article::observe(ArticleObserver::class);
        View::share('siteSettings', SiteSettings::getInstance());
        View::share('currencyRates', app(CbrRatesService::class)->getRates());

        View::composer('layouts.partials.header', function ($view): void {
            $view->with('headerCitySelectBase', self::getHeaderCitySelectBase());
        });

        View::composer(['layouts.main', 'layouts.section-index', 'layouts.app'], function ($view): void {
            $data = $view->getData();
            $base = $data['citySelectBase'] ?? $data['sectionBaseForRedirect'] ?? self::getHeaderCitySelectBase();
            $hasCity = isset($data['city']) && $data['city'];
            if ($base !== '' && $base !== null && ! $hasCity) {
                $view->with('redirectToCityIfStored', true);
                $view->with('sectionBaseForRedirect', is_string($base) ? trim($base) : $base);
            }
        });
    }

    private static function getHeaderCitySelectBase(): string
    {
        $seg = request()->segments();
        if (count($seg) < 1) {
            return '';
        }
        $first = $seg[0];
        $sectionBases = [
            'vklady' => \App\Models\DepositCategory::class,
            'kredity' => \App\Models\CreditCategory::class,
            'zaimy' => \App\Models\LoanCategory::class,
            'banki' => \App\Models\BankCategory::class,
        ];
        if ($first === 'karty') {
            if (count($seg) === 1) {
                return 'karty';
            }
            if (count($seg) >= 3 && ($seg[1] ?? '') === 'category') {
                return 'karty/category/' . ($seg[2] ?? '');
            }
            return 'karty';
        }
        if (! isset($sectionBases[$first])) {
            return '';
        }
        $categoryClass = $sectionBases[$first];
        if (count($seg) === 1) {
            return $first;
        }
        if (count($seg) === 3) {
            return $first . '/' . $seg[1];
        }
        if (count($seg) === 2) {
            $cacheKey = 'header_section_' . $first . '_' . $seg[1];
            $isCategory = Cache::remember($cacheKey, 3600, fn () => $categoryClass::where('slug', $seg[1])->exists());
            return $isCategory ? $first . '/' . $seg[1] : $first;
        }
        return '';
    }
}
