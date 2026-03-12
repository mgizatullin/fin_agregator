<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DepositReviewController extends Controller
{
    public function store(Request $request, Deposit $deposit): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'bank_id' => ['required', 'integer', 'exists:banks,id'],
            'service' => ['required', 'string', 'in:Вклад,Кредит,Дебетовая карта,Кредитная карта'],
            'personal_data_consent' => ['required', 'accepted'],
        ]);

        Review::create([
            'reviewable_type' => Deposit::class,
            'reviewable_id' => $deposit->id,
            'bank_id' => $data['bank_id'],
            'service' => $data['service'],
            'title' => $data['title'] ?? '',
            'body' => $data['body'],
            'rating' => $data['rating'],
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);

        return redirect()
            ->to(url('/vklady/' . $deposit->slug) . '#deposit-reviews')
            ->with('status', 'Спасибо! Ваш отзыв отправлен.');
    }
}

