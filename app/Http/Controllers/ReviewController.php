<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Card;
use App\Models\Credit;
use App\Models\Loan;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function storeCredit(Request $request, Credit $credit): RedirectResponse
    {
        $data = $this->validateReview($request);
        Review::create([
            'reviewable_type' => Credit::class,
            'reviewable_id' => $credit->id,
            'bank_id' => $credit->bank_id,
            'service' => $data['service'],
            'title' => $data['title'] ?? '',
            'body' => $data['body'],
            'rating' => $data['rating'],
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);
        return redirect()->to(url('/kredity/' . $credit->slug) . '#product-reviews')->with('status', 'Спасибо! Ваш отзыв отправлен.');
    }

    public function storeCard(Request $request, Card $card): RedirectResponse
    {
        $data = $this->validateReview($request);
        Review::create([
            'reviewable_type' => Card::class,
            'reviewable_id' => $card->id,
            'bank_id' => $card->bank_id,
            'service' => $data['service'],
            'title' => $data['title'] ?? '',
            'body' => $data['body'],
            'rating' => $data['rating'],
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);
        return redirect()->to(url('/karty/' . $card->slug) . '#product-reviews')->with('status', 'Спасибо! Ваш отзыв отправлен.');
    }

    public function storeLoan(Request $request, Loan $loan): RedirectResponse
    {
        $data = $this->validateReview($request);
        Review::create([
            'reviewable_type' => Loan::class,
            'reviewable_id' => $loan->id,
            'bank_id' => null,
            'service' => $data['service'],
            'title' => $data['title'] ?? '',
            'body' => $data['body'],
            'rating' => $data['rating'],
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);
        return redirect()->to(url('/zaimy/' . $loan->slug) . '#product-reviews')->with('status', 'Спасибо! Ваш отзыв отправлен.');
    }

    public function storeBank(Request $request, Bank $bank): RedirectResponse
    {
        $data = $this->validateReview($request);
        Review::create([
            'reviewable_type' => Bank::class,
            'reviewable_id' => $bank->id,
            'bank_id' => $bank->id,
            'service' => $data['service'],
            'title' => $data['title'] ?? '',
            'body' => $data['body'],
            'rating' => $data['rating'],
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);
        return redirect()->to(url('/banki/' . $bank->slug) . '#product-reviews')->with('status', 'Спасибо! Ваш отзыв отправлен.');
    }

    private function validateReview(Request $request): array
    {
        return $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'bank_id' => ['nullable', 'integer', 'exists:banks,id'],
            'service' => ['required', 'string', 'max:100'],
            'personal_data_consent' => ['required', 'accepted'],
        ]);
    }
}
