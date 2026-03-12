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
use App\Http\Controllers\ReviewController;
use App\Models\City;
use App\Models\HomePageSetting;
use App\Models\Article;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $settings = HomePageSetting::instance();
    $posts = Article::query()
        ->with('category')
        ->where('is_published', true)
        ->latest('published_at')
        ->take(4)
        ->get();
    return view('home_new', [
        'settings' => $settings,
        'posts' => $posts,
        'seo_title' => $settings->seo_title,
        'seo_description' => $settings->seo_description,
        'title' => $settings->hero_title ?? config('app.name', 'Финансовый маркетплейс'),
    ]);
})->name('home');

Route::get('/city-dialog', CityDialogController::class)->name('city.dialog');

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

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/category/{slug}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/api/deposits/{deposit}/conditions', [DepositController::class, 'conditions'])->name('api.deposits.conditions');
