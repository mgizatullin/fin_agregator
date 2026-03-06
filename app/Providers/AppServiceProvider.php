<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\SiteSettings;
use App\Observers\ArticleObserver;
use Carbon\Carbon;
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
        Carbon::setLocale('ru');
        Article::observe(ArticleObserver::class);
        View::share('siteSettings', SiteSettings::getInstance());
    }
}
