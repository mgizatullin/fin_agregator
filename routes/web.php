<?php

use App\Http\Controllers\BankCategoryController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CardCategoryController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\CityDialogController;
use App\Http\Controllers\CreditCategoryController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\DepositCategoryController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\DepositReviewController;
use App\Http\Controllers\LoanCategoryController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ReviewController;
use App\Models\City;
use App\Models\HomePageSetting;
use App\Models\Article;
use App\Models\SiteSettings;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $settings = HomePageSetting::instance();
    $posts = Article::query()
        ->with('category')
        ->where('is_published', true)
        ->latest('published_at')
        ->take(4)
        ->get();
    try {
        $currencyRatesWithChange = app(\App\Services\CbrRatesService::class)->getRatesWithChange();
    } catch (\Throwable $e) {
        $currencyRatesWithChange = ['date' => null, 'date_label' => '', 'rates' => []];
    }
    return view('home_new', [
        'settings' => $settings,
        'posts' => $posts,
        'seo_title' => $settings->seo_title,
        'seo_description' => $settings->seo_description,
        'title' => $settings->hero_title ?? config('app.name', 'Финансовый маркетплейс'),
        'currencyRatesWithChange' => $currencyRatesWithChange,
    ]);
})->name('home');

Route::get('/currency-calculator', function () {
    return view('currency-calculator-placeholder');
})->name('currency.calculator');

Route::get('/test-leadgid', function () {
    return app(\App\Services\LeadgidService::class)->testConnection();
});

Route::get('/test-network', function () {
    return [
        'dns' => gethostbyname('api.leadgid.com'),
        'curl' => shell_exec('curl -I https://api.leadgid.com 2>&1'),
    ];
});

Route::get('/about', function () {
    $siteSettings = SiteSettings::getInstance();

    return view('about-project', [
        'siteSettings' => $siteSettings,
        'seo_title' => $siteSettings->about_project_seo_title ?: ($siteSettings->about_project_team_title ?: 'О проекте'),
        'seo_description' => $siteSettings->about_project_seo_description ?: ($siteSettings->about_project_reviews_description ?: ($siteSettings->about_project_approach_description ?: '')),
        'title' => 'О проекте',
    ]);
});

Route::get('/about.html', function () {
    $siteSettings = SiteSettings::getInstance();

    return view('about-project', [
        'siteSettings' => $siteSettings,
        'seo_title' => $siteSettings->about_project_seo_title ?: ($siteSettings->about_project_team_title ?: 'О проекте'),
        'seo_description' => $siteSettings->about_project_seo_description ?: ($siteSettings->about_project_reviews_description ?: ($siteSettings->about_project_approach_description ?: '')),
        'title' => 'О проекте',
    ]);
});

Route::get('/city-dialog', CityDialogController::class)->name('city.dialog');

Route::get('/api/cities/search', function (\Illuminate\Http\Request $request) {
    $q = $request->input('q', '');
    $q = is_string($q) ? trim($q) : '';
    if (mb_strlen($q) < 2) {
        return response()->json(['cities' => []]);
    }
    $qLower = mb_strtolower($q, 'UTF-8');
    $first = mb_substr($q, 0, 1, 'UTF-8');
    $firstUpper = mb_strtoupper($first, 'UTF-8');
    $firstLower = mb_strtolower($first, 'UTF-8');
    $escape = fn ($s) => str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $s);
    $candidates = \App\Models\City::query()
        ->where('is_active', true)
        ->where(function ($query) use ($firstUpper, $firstLower, $escape) {
            $query->where('name', 'like', $escape($firstUpper) . '%')
                ->orWhere('name', 'like', $escape($firstLower) . '%');
        })
        ->orderBy('name')
        ->limit(300)
        ->get(['id', 'name', 'slug']);
    $cities = $candidates->filter(function ($city) use ($qLower) {
        return mb_stripos($city->name, $qLower, 0, 'UTF-8') === 0;
    })->take(50)->values();
    return response()->json(['cities' => $cities->toArray()]);
})->name('api.cities.search');

// Cards: /karty, /karty/{card|city}, /karty/category/{slug}, /karty/category/{slug}/{city}
Route::get('/karty', [CardController::class, 'index'])->name('cards.index');
Route::get('/karty/category/{slug}', [CardCategoryController::class, 'show'])->name('cards.category.show');
Route::get('/karty/category/{slug}/{citySlug}', function (string $slug, string $citySlug) {
    $city = City::where('slug', $citySlug)->where('is_active', true)->first();
    if (!$city) {
        abort(404);
    }
    return app(CardCategoryController::class)->show(request(), $slug, $citySlug);
})->name('cards.category.city');
Route::get('/karty/{first}', function (string $first) {
    $card = \App\Models\Card::where('slug', $first)->where('is_active', true)->first();
    if ($card) {
        return app(CardController::class)->show(request(), $first);
    }
    $city = City::where('slug', $first)->where('is_active', true)->first();
    if ($city) {
        return app(CardController::class)->index(request(), $first);
    }
    abort(404);
})->name('cards.show');

// Credits: /kredity, /kredity/{product|category|city}, /kredity/{category}/{city}
Route::get('/kredity', [CreditController::class, 'index'])->name('credits.index');
Route::get('/kredity/{first}/{second}', function (string $first, string $second) {
    $category = \App\Models\CreditCategory::where('slug', $first)->first();
    $city = City::where('slug', $second)->where('is_active', true)->first();
    if ($category && $city) {
        return app(CreditCategoryController::class)->show(request(), $first, $second);
    }
    abort(404);
})->name('credits.category.city');
Route::get('/kredity/{first}', function (string $first) {
    $category = \App\Models\CreditCategory::where('slug', $first)->first();
    if ($category) {
        return app(CreditCategoryController::class)->show(request(), $first, null);
    }
    $credit = \App\Models\Credit::where('slug', $first)->where('is_active', true)->first();
    if ($credit) {
        return app(CreditController::class)->show(request(), $first);
    }
    $city = City::where('slug', $first)->where('is_active', true)->first();
    if ($city) {
        return app(CreditController::class)->index(request(), $first);
    }
    abort(404);
})->name('credits.category.show');

// Deposits: /vklady, /vklady/{product|category|city}, /vklady/{category}/{city}
Route::get('/vklady', [DepositController::class, 'index'])->name('deposits.index');
Route::get('/vklady/{first}/{second}', function (string $first, string $second) {
    $category = \App\Models\DepositCategory::where('slug', $first)->first();
    $city = City::where('slug', $second)->where('is_active', true)->first();
    if ($category && $city) {
        return app(DepositCategoryController::class)->show(request(), $first, $second);
    }
    abort(404);
})->name('deposits.category.city');
Route::get('/vklady/{first}', function (string $first) {
    $deposit = \App\Models\Deposit::where('slug', $first)->where('is_active', true)->first();
    if ($deposit) {
        return app(DepositController::class)->show(request(), $first);
    }
    $category = \App\Models\DepositCategory::where('slug', $first)->first();
    if ($category) {
        return app(DepositCategoryController::class)->show(request(), $first, null);
    }
    $city = City::where('slug', $first)->where('is_active', true)->first();
    if ($city) {
        return app(DepositController::class)->index(request(), $first);
    }
    abort(404);
})->name('deposits.category.show');

// Loans: /zaimy, /zaimy/{product|category|city}, /zaimy/{category}/{city}
Route::get('/zaimy', [LoanController::class, 'index'])->name('loans.index');
Route::get('/zaimy/{first}/{second}', function (string $first, string $second) {
    $category = \App\Models\LoanCategory::where('slug', $first)->first();
    $city = City::where('slug', $second)->where('is_active', true)->first();
    if ($category && $city) {
        return app(LoanCategoryController::class)->show(request(), $first, $second);
    }
    abort(404);
})->name('loans.category.city');
Route::get('/zaimy/{first}', function (string $first) {
    $loan = \App\Models\Loan::where('slug', $first)->where('is_active', true)->first();
    if ($loan) {
        return app(LoanController::class)->show(request(), $first);
    }
    $category = \App\Models\LoanCategory::where('slug', $first)->first();
    if ($category) {
        return app(LoanCategoryController::class)->show(request(), $first, null);
    }
    $city = City::where('slug', $first)->where('is_active', true)->first();
    if ($city) {
        return app(LoanController::class)->index(request(), $first);
    }
    abort(404);
})->name('loans.category.show');

// Banks: /banki, /banki/{product|category|city}, /banki/{category}/{city}
Route::get('/banki', [BankController::class, 'index'])->name('banks.index');
Route::get('/banki/{first}/{second}', function (string $first, string $second) {
    $category = \App\Models\BankCategory::where('slug', $first)->first();
    $city = City::where('slug', $second)->where('is_active', true)->first();
    if ($category && $city) {
        return app(BankCategoryController::class)->show(request(), $first, $second);
    }
    abort(404);
})->name('banks.category.city');
Route::get('/banki/{first}', function (string $first) {
    $bank = \App\Models\Bank::where('slug', $first)->where('is_active', true)->first();
    if ($bank) {
        return app(BankController::class)->show(request(), $first);
    }
    $category = \App\Models\BankCategory::where('slug', $first)->first();
    if ($category) {
        return app(BankCategoryController::class)->show(request(), $first, null);
    }
    $city = City::where('slug', $first)->where('is_active', true)->first();
    if ($city) {
        return app(BankController::class)->index(request(), $first);
    }
    abort(404);
})->name('banks.category.show');

Route::post('/vklady/{deposit}/reviews', [DepositReviewController::class, 'store'])->name('deposits.reviews.store');
Route::post('/kredity/{credit:slug}/reviews', [ReviewController::class, 'storeCredit'])->name('credits.reviews.store');
Route::post('/karty/{card:slug}/reviews', [ReviewController::class, 'storeCard'])->name('cards.reviews.store');
Route::post('/zaimy/{loan:slug}/reviews', [ReviewController::class, 'storeLoan'])->name('loans.reviews.store');
Route::post('/banki/{bank:slug}/reviews', [ReviewController::class, 'storeBank'])->name('banks.reviews.store');

Route::get('/search', [\App\Http\Controllers\SearchController::class, 'index'])->name('search');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/category/{slug}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/api/deposits/{deposit}/conditions', [DepositController::class, 'conditions'])->name('api.deposits.conditions');

// Static pages: /{slug} (must be last)
Route::get('/{slug}', [PageController::class, 'show'])
    ->where('slug', '[A-Za-z0-9\\-]+')
    ->name('pages.show');
