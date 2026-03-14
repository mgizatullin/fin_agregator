<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Bank;
use App\Models\Card;
use App\Models\Credit;
use App\Models\Deposit;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            $isSqlite = DB::connection()->getDriverName() === 'sqlite';
            $likes = $this->buildLikePatterns($q);

            $credits = Credit::query()
                ->where('is_active', true)
                ->where(function (Builder $query) use ($likes, $isSqlite) {
                    $this->whereLikeAny($query, 'name', $likes, $isSqlite);
                    $query->orWhereHas('bank', function (Builder $b) use ($likes, $isSqlite) {
                        $this->whereLikeAny($b, 'name', $likes, $isSqlite);
                    });
                })
                ->with('bank')
                ->orderBy('name')
                ->limit(20)
                ->get();

            $deposits = Deposit::query()
                ->where('is_active', true)
                ->where(function (Builder $query) use ($likes, $isSqlite) {
                    $this->whereLikeAny($query, 'name', $likes, $isSqlite);
                    $query->orWhereHas('bank', function (Builder $b) use ($likes, $isSqlite) {
                        $this->whereLikeAny($b, 'name', $likes, $isSqlite);
                    });
                })
                ->with('bank')
                ->orderBy('name')
                ->limit(20)
                ->get();

            $banks = Bank::query()
                ->where('is_active', true)
                ->where(function (Builder $query) use ($likes, $isSqlite) {
                    $this->whereLikeAny($query, 'name', $likes, $isSqlite);
                })
                ->orderBy('name')
                ->limit(20)
                ->get();

            $cards = Card::query()
                ->where('is_active', true)
                ->where(function (Builder $query) use ($likes, $isSqlite) {
                    $this->whereLikeAny($query, 'name', $likes, $isSqlite);
                    $query->orWhereHas('bank', function (Builder $b) use ($likes, $isSqlite) {
                        $this->whereLikeAny($b, 'name', $likes, $isSqlite);
                    });
                })
                ->with('bank')
                ->orderBy('name')
                ->limit(20)
                ->get();

            $loans = Loan::query()
                ->where('is_active', true)
                ->where(function (Builder $query) use ($likes, $isSqlite) {
                    $this->whereLikeAny($query, 'name', $likes, $isSqlite);
                    $query->orWhere(function (Builder $q) use ($likes, $isSqlite) {
                        $this->whereLikeAny($q, 'company_name', $likes, $isSqlite);
                    });
                })
                ->orderBy('name')
                ->limit(20)
                ->get();

            $articles = Article::query()
                ->where('is_published', true)
                ->where(function (Builder $query) use ($likes, $isSqlite) {
                    $this->whereLikeAny($query, 'title', $likes, $isSqlite);
                    $query->orWhere(function (Builder $q) use ($likes, $isSqlite) {
                        $this->whereLikeAny($q, 'excerpt', $likes, $isSqlite);
                    });
                })
                ->with('category')
                ->orderByDesc('published_at')
                ->limit(20)
                ->get();
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
        ]);
    }

    /**
     * Add LIKE condition.
     *
     * @param bool $or use orWhere instead of where
     */
    private function whereLike(Builder $query, string $column, string $like, bool $or = false): void
    {
        $method = $or ? 'orWhere' : 'where';
        $query->{$method}($column, 'like', $like);
    }

    /**
     * Build escaped LIKE patterns (%q%) and case-variants for SQLite.
     *
     * @return array<int, string>
     */
    private function buildLikePatterns(string $q): array
    {
        $variants = [
            $q,
            mb_strtolower($q, 'UTF-8'),
            mb_strtoupper($q, 'UTF-8'),
        ];
        $variants = array_values(array_unique(array_filter($variants, fn ($v) => is_string($v) && $v !== '')));

        return array_map(function (string $v): string {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $v);
            return '%' . $escaped . '%';
        }, $variants);
    }

    /**
     * WHERE column LIKE (pattern1 OR pattern2 OR ...)
     * For non-SQLite databases we only need the first pattern (usually already case-insensitive via collation).
     *
     * @param array<int, string> $likes
     */
    private function whereLikeAny(Builder $query, string $column, array $likes, bool $isSqlite): void
    {
        if (! $isSqlite) {
            $like = $likes[0] ?? null;
            if ($like !== null) {
                $query->where($column, 'like', $like);
            }
            return;
        }

        $query->where(function (Builder $q) use ($column, $likes): void {
            $first = true;
            foreach ($likes as $like) {
                if ($first) {
                    $q->where($column, 'like', $like);
                    $first = false;
                } else {
                    $q->orWhere($column, 'like', $like);
                }
            }
        });
    }
}
