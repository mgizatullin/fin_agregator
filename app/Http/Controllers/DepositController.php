<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesLoadMorePagination;
use App\Http\Helpers\SectionRouteResolver;
use App\Models\Bank;
use App\Models\Deposit;
use App\Models\DepositCategory;
use App\Models\DepositCondition;
use App\Models\Review;
use App\Models\SectionSetting;
use App\Models\SiteSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class DepositController extends Controller
{
    use HandlesLoadMorePagination;

    public function index(Request $request, ?string $citySlug = null): View|Response
    {
        $city = SectionRouteResolver::resolveCity($citySlug);

        $baseQuery = Deposit::query()
            ->with(['bank', 'currencies.conditions'])
            ->where('is_active', true);

        $filterMeta = $this->buildFilterMeta(clone $baseQuery);

        $query = clone $baseQuery;
        $this->applyFilters($query, $request);

        $items = $query->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        if ($response = $this->loadMoreResponse($request, $items, 'deposits.partials.list-items', [
            'items' => $items,
        ])) {
            return $response;
        }

        $setting = SectionSetting::forType('deposits');
        $section = (object) [
            'title' => $setting?->title ?: 'Вклады',
            'subtitle' => $setting?->subtitle ?: '',
            'description' => $setting?->description ?: '',
        ];

        $categories = DepositCategory::orderBy('title')->get();

        if ($city) {
            $seo_title = filled($setting?->seo_title_template)
                ? SectionRouteResolver::parseTemplate($setting->seo_title_template, $section, $city)
                : ($setting?->seo_title ? $setting->seo_title.' в '.($city->name_prepositional ?? $city->name) : null);
            $seo_description = filled($setting?->seo_description_template)
                ? SectionRouteResolver::parseTemplate($setting->seo_description_template, $section, $city)
                : SectionRouteResolver::sectionDescription($setting?->seo_description, $city);
            $page_h1 = filled($setting?->h1_template)
                ? SectionRouteResolver::parseTemplate($setting->h1_template, $section, $city)
                : SectionRouteResolver::sectionTitle($section, $city);
            $page_content = filled($setting?->content_template)
                ? SectionRouteResolver::parseTemplate($setting->content_template, $section, $city)
                : ($section->description ?? '');
        } else {
            $seo_title = $setting?->seo_title;
            $seo_description = $setting?->seo_description;
            $page_h1 = $section->title;
            $page_content = $section->description ?? '';
        }

        $title = $page_h1;
        $latestSectionReviews = Review::query()
            ->with(['bank', 'reviewable'])
            ->where('reviewable_type', Deposit::class)
            ->where('is_published', true)
            ->latest()
            ->limit(4)
            ->get();

        return view('deposits.index', array_merge([
            'items' => $items,
            'section' => $section,
            'categories' => $categories,
            'city' => $city,
            'seo_title' => $seo_title,
            'seo_description' => $seo_description,
            'title' => $title,
            'page_h1' => $page_h1,
            'page_content' => $page_content,
            'faq_title' => $setting?->faq_title,
            'faq_description' => $setting?->faq_description,
            'faq_items' => $setting?->faq_items ?? [],
            'reviews_block_title' => $setting?->reviews_block_title,
            'latestSectionReviews' => $latestSectionReviews,
            'filterMeta' => $filterMeta,
        ], $city ? [] : ['redirectToCityIfStored' => true, 'sectionBaseForRedirect' => 'vklady']));
    }

    /**
     * Show a single deposit by slug (material page). No city in URL.
     */
    public function show(Request $request, string $slug): View
    {
        $deposit = Deposit::with(['bank', 'currencies.conditions', 'reviews.bank'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $pageHeadline = $deposit->pageHeadline();
        $siteDisplayName = SiteSettings::getInstance()->displayNameForTitle();

        $section = (object) [
            'title' => $pageHeadline,
            'subtitle' => null,
        ];

        $banks = Bank::where('is_active', true)->orderBy('name')->get();

        return view('deposits.show', array_merge(compact('deposit', 'section', 'banks'), [
            'sectionIndexUrl' => url_canonical(route('deposits.index')),
            'sectionIndexTitle' => 'Вклады',
            'seo_title' => $pageHeadline.' — '.$siteDisplayName,
            'seo_description' => null,
            'title' => $pageHeadline,
        ]));
    }

    /**
     * API для калькулятора: условия ставок по валютам.
     */
    public function conditions(Deposit $deposit): JsonResponse
    {
        $deposit->load(['currencies.conditions' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')]);

        $currencies = $deposit->currencies->map(function ($currency) {
            return [
                'currency' => $currency->currency_code,
                'conditions' => $currency->conditions->map(fn ($c) => [
                    'term_days_min' => $c->term_days_min,
                    'term_days_max' => $c->term_days_max,
                    'amount_min' => $c->amount_min !== null ? (float) $c->amount_min : null,
                    'amount_max' => $c->amount_max !== null ? (float) $c->amount_max : null,
                    'interest_rate' => (float) $c->rate,
                ])->values()->all(),
            ];
        })->values()->all();

        return response()->json(['currencies' => $currencies]);
    }

    protected function applyFilters(Builder $query, Request $request): void
    {
        $amount = (float) $request->input('amount', 0);
        $term = (int) $request->integer('term', 0);
        $rate = (float) $request->input('rate', 0);
        $depositType = trim((string) $request->input('deposit_type', ''));

        if ($depositType !== '') {
            $query->where('deposit_type', $depositType);
        }

        if ($amount <= 0 && $term <= 0 && $rate <= 0) {
            return;
        }

        $query->whereHas('currencies.conditions', function (Builder $conditionsQuery) use ($amount, $term, $rate): void {
            $conditionsQuery->where('is_active', true);

            if ($rate > 0) {
                // Ищем вклад, где в любой ячейке условий ставка не ниже выбранной.
                $conditionsQuery->where('rate', '>=', $rate);
            }

            if ($term > 0) {
                // Ищем вклад, где есть срок не меньше выбранного.
                $conditionsQuery->where(function (Builder $termQuery) use ($term): void {
                    $termQuery->where(function (Builder $q) use ($term): void {
                        $q->whereNotNull('term_days_max')
                            ->where('term_days_max', '>=', $term);
                    })->orWhere(function (Builder $q) use ($term): void {
                        $q->whereNull('term_days_max')
                            ->whereNotNull('term_days_min')
                            ->where('term_days_min', '>=', $term);
                    })->orWhere(function (Builder $q): void {
                        $q->whereNull('term_days_min')
                            ->whereNull('term_days_max');
                    });
                });
            }

            if ($amount > 0) {
                $conditionsQuery
                    ->where(function (Builder $amountQuery) use ($amount): void {
                        $amountQuery->whereNull('amount_min')
                            ->orWhere('amount_min', '<=', $amount);
                    })
                    ->where(function (Builder $amountQuery) use ($amount): void {
                        $amountQuery->whereNull('amount_max')
                            ->orWhere('amount_max', '>=', $amount);
                    });
            }
        });
    }

    protected function buildFilterMeta(Builder $query): array
    {
        $depositIds = (clone $query)->pluck('deposits.id');

        $conditions = DepositCondition::query()
            ->where('is_active', true)
            ->whereHas('depositCurrency', fn (Builder $currencyQuery): Builder => $currencyQuery->whereIn('deposit_id', $depositIds))
            ->get(['term_days_min', 'term_days_max', 'amount_min', 'amount_max', 'rate']);

        $maxAmount = (float) $conditions
            ->flatMap(fn (DepositCondition $condition): array => [
                $condition->amount_min !== null ? (float) $condition->amount_min : 0.0,
                $condition->amount_max !== null ? (float) $condition->amount_max : 0.0,
            ])
            ->max();

        $maxTerm = (int) $conditions
            ->flatMap(fn (DepositCondition $condition): array => [
                $condition->term_days_min !== null ? (int) $condition->term_days_min : 0,
                $condition->term_days_max !== null ? (int) $condition->term_days_max : 0,
            ])
            ->max();

        $maxRate = (float) $conditions->max(fn (DepositCondition $condition): float => $condition->rate !== null ? (float) $condition->rate : 0.0);

        $depositTypes = (clone $query)
            ->whereNotNull('deposit_type')
            ->where('deposit_type', '!=', '')
            ->orderBy('deposit_type')
            ->pluck('deposit_type')
            ->unique()
            ->values();

        $amountFilterMax = $maxAmount > 30_000_000 ? (int) ceil($maxAmount) : 30_000_000;

        return [
            'max_amount' => $amountFilterMax,
            'max_term' => max(0, $maxTerm),
            'max_rate' => round($maxRate, 2),
            'deposit_types' => $depositTypes,
        ];
    }
}
