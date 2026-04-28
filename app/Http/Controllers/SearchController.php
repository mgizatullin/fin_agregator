<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Bank;
use App\Models\Card;
use App\Models\Credit;
use App\Models\Deposit;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $q = $request->input('q', '');
        $q = is_string($q) ? trim($q) : '';
        $q = mb_convert_encoding($q, 'UTF-8', 'UTF-8');

        $credits = collect();
        $deposits = collect();
        $banks = collect();
        $cards = collect();
        $loans = collect();
        $articles = collect();

        if ($q !== '') {
            $credits = Credit::query()
                ->where('is_active', true)
                ->with('bank')
                ->orderBy('name')
                ->get()
                ->filter(fn ($credit) => $this->matchesAny($q, $credit->name, $credit->bank?->name))
                ->take(20)
                ->values();

            $deposits = Deposit::query()
                ->where('is_active', true)
                ->with('bank')
                ->orderBy('name')
                ->get()
                ->filter(fn ($deposit) => $this->matchesAny($q, $deposit->name, $deposit->bank?->name))
                ->take(20)
                ->values();

            $banks = Bank::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->filter(fn ($bank) => $this->matchesAny($q, $bank->name))
                ->take(20)
                ->values();

            $cards = Card::query()
                ->where('is_active', true)
                ->with('bank')
                ->orderBy('name')
                ->get()
                ->filter(fn ($card) => $this->matchesAny($q, $card->name, $card->bank?->name))
                ->take(20)
                ->values();

            $loans = Loan::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->filter(fn ($loan) => $this->matchesAny($q, $loan->name, $loan->company_name))
                ->take(20)
                ->values();

            $articles = Article::query()
                ->where('is_published', true)
                ->with('category')
                ->orderByDesc('published_at')
                ->get()
                ->filter(fn ($article) => $this->matchesAny($q, $article->title, $article->excerpt))
                ->take(20)
                ->values();
        }

        $total = $credits->count() + $deposits->count() + $banks->count() + $cards->count() + $loans->count() + $articles->count();

        return view('search.index', [
            'q' => $q,
            'credits' => $credits,
            'deposits' => $deposits,
            'banks' => $banks,
            'cards' => $cards,
            'loans' => $loans,
            'articles' => $articles,
            'total' => $total,
            'seo_title' => 'Поиск по сайту finvito.ru',
            'title' => 'Поиск по сайту finvito.ru',
        ]);
    }

    private function matchesAny(string $search, mixed ...$values): bool
    {
        foreach ($values as $value) {
            if ($value !== null && mb_stripos((string) $value, $search, 0, 'UTF-8') !== false) {
                return true;
            }
        }

        return false;
    }
}
