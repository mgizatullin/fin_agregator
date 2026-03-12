<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\SiteSettings;
use App\Observers\ArticleObserver;
use App\Services\CbrRatesService;
use Carbon\Carbon;
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
        //
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
    }
}
