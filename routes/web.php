<?php

use App\Http\Controllers\BankCategoryController;
use App\Http\Controllers\BankController;
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
Route::get('/vklady/{slug}', [DepositCategoryController::class, 'show'])->name('deposits.category.show');

Route::get('/zaimy', [LoanController::class, 'index'])->name('loans.index');
Route::get('/zaimy/{slug}', [LoanCategoryController::class, 'show'])->name('loans.category.show');

Route::get('/banki', [BankController::class, 'index'])->name('banks.index');
Route::get('/banki/{slug}', [BankCategoryController::class, 'show'])->name('banks.category.show');
