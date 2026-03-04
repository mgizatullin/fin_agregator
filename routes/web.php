<?php

use App\Http\Controllers\BankCategoryController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CardCategoryController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\CreditCategoryController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\DepositCategoryController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\LoanCategoryController;
use App\Http\Controllers\LoanController;
use App\Models\HomePageSetting;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $settings = HomePageSetting::instance();
    return view('home_new', [
        'pageTitle' => config('app.name', 'Финансовый маркетплейс'),
        'metaDescription' => config('app.name'),
        'metaKeywords' => '',
        'settings' => $settings,
    ]);
})->name('home');

Route::get('/karty', [CardController::class, 'index'])->name('cards.index');
Route::get('/karty/category/{slug}', [CardCategoryController::class, 'show'])->name('cards.category.show');
Route::get('/karty/{slug}', [CardController::class, 'show'])->name('cards.show');

Route::get('/kredity', [CreditController::class, 'index'])->name('credits.index');
Route::get('/kredity/{slug}', function (string $slug) {
    $credit = \App\Models\Credit::where('slug', $slug)->where('is_active', true)->first();
    if ($credit) {
        return app(CreditController::class)->show(request(), $slug);
    }
    return app(CreditCategoryController::class)->show(request(), $slug);
})->name('credits.category.show');

Route::get('/vklady', [DepositController::class, 'index'])->name('deposits.index');
Route::get('/vklady/{slug}', function (string $slug) {
    $deposit = \App\Models\Deposit::where('slug', $slug)->where('is_active', true)->first();
    if ($deposit) {
        return app(DepositController::class)->show(request(), $slug);
    }
    return app(DepositCategoryController::class)->show(request(), $slug);
})->name('deposits.category.show');

Route::get('/zaimy', [LoanController::class, 'index'])->name('loans.index');
Route::get('/zaimy/{slug}', function (string $slug) {
    $loan = \App\Models\Loan::where('slug', $slug)->where('is_active', true)->first();
    if ($loan) {
        return app(LoanController::class)->show(request(), $slug);
    }
    return app(LoanCategoryController::class)->show(request(), $slug);
})->name('loans.category.show');

Route::get('/banki', [BankController::class, 'index'])->name('banks.index');
Route::get('/banki/{slug}', function (string $slug) {
    $bank = \App\Models\Bank::where('slug', $slug)->where('is_active', true)->first();
    if ($bank) {
        return app(BankController::class)->show(request(), $slug);
    }
    return app(BankCategoryController::class)->show(request(), $slug);
})->name('banks.category.show');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/category/{slug}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
