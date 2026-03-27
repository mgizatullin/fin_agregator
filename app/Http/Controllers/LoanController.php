<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesLoadMorePagination;
use App\Http\Helpers\SectionRouteResolver;
use App\Models\Loan;
use App\Models\LoanCategory;
use App\Models\SectionSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class LoanController extends Controller
{
    use HandlesLoadMorePagination;

    public function index(Request $request, ?string $citySlug = null): View|Response
    {
        $city = SectionRouteResolver::resolveCity($citySlug);

        $baseQuery = Loan::query()
            ->where('is_active', true)
            ->orderBy('name');

        $filterMeta = $this->buildFilterMeta(clone $baseQuery);

        $query = clone $baseQuery;
        $this->applyFilters($query, $request);

        $items = $query->paginate(20)->withQueryString();

        if ($response = $this->loadMoreResponse($request, $items, 'loans.partials.list-items', [
            'items' => $items,
        ])) {
            return $response;
        }

        $setting = SectionSetting::forType('loans');
        $section = (object) [
            'title' => $setting?->title ?: 'Займы',
            'subtitle' => $setting?->subtitle ?: '',
            'description' => $setting?->description ?: '',
        ];

        $categories = LoanCategory::orderBy('title')->get();

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

        return view('loans.index', array_merge([
            'items' => $items,
            'section' => $section,
            'categories' => $categories,
            'city' => $city,
            'seo_title' => $seo_title,
            'seo_description' => $seo_description,
            'title' => $title,
            'page_h1' => $page_h1,
            'page_content' => $page_content,
            'filterMeta' => $filterMeta,
        ], $city ? [] : ['redirectToCityIfStored' => true, 'sectionBaseForRedirect' => 'zaimy']));
    }

    /**
     * Show a single loan by slug (material page). No city in URL.
     */
    public function show(Request $request, string $slug): View
    {
        $loan = Loan::with('reviews.bank')
            ->withCount('reviews')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $section = (object) [
            'title' => $loan->name.($loan->company_name ? ' — '.$loan->company_name : ''),
            'subtitle' => $loan->company_name ?? '',
        ];

        return view('loans.show', array_merge(compact('loan', 'section'), [
            'sectionIndexUrl' => url_canonical(route('loans.index')),
            'sectionIndexTitle' => 'Займы',
            'seo_title' => null,
            'seo_description' => null,
            'title' => $section->title,
        ]));
    }

    protected function applyFilters(Builder $query, Request $request): void
    {
        $amount = (float) $request->input('amount', 0);
        $term = (int) $request->integer('term', 0);
        $termNoInterest = (int) $request->integer('term_no_interest', 0);
        $rate = (float) $request->input('rate', 0);
        $psk = (float) $request->input('psk', 0);

        if ($amount > 0) {
            $query->where(function (Builder $amountQuery) use ($amount): void {
                $amountQuery->where(function (Builder $q) use ($amount): void {
                    $q->whereNotNull('max_amount')->where('max_amount', '>=', $amount);
                })->orWhereNull('max_amount');
            })->where(function (Builder $amountQuery) use ($amount): void {
                $amountQuery->where(function (Builder $q) use ($amount): void {
                    $q->whereNotNull('min_amount')->where('min_amount', '<=', $amount);
                })->orWhereNull('min_amount');
            });
        }

        if ($term > 0) {
            $query->where(function (Builder $termQuery) use ($term): void {
                $termQuery->where(function (Builder $q) use ($term): void {
                    $q->whereNotNull('term_days')->where('term_days', '>=', $term);
                })->orWhere(function (Builder $q) use ($term): void {
                    $q->whereNotNull('term_days_min')->where('term_days_min', '<=', $term);
                });
            });
        }

        if ($termNoInterest > 0) {
            $query->where(function (Builder $termNoInterestQuery) use ($termNoInterest): void {
                $termNoInterestQuery->where(function (Builder $q) use ($termNoInterest): void {
                    $q->whereNotNull('term_no_interest')->where('term_no_interest', '>=', $termNoInterest);
                })->orWhere(function (Builder $q) use ($termNoInterest): void {
                    $q->whereNotNull('term_no_interest_min')->where('term_no_interest_min', '<=', $termNoInterest);
                });
            });
        }

        if ($rate > 0) {
            $query->where(function (Builder $rateQuery) use ($rate): void {
                $rateQuery->where(function (Builder $q) use ($rate): void {
                    $q->whereNotNull('rate')->where('rate', '<=', $rate);
                })->orWhere(function (Builder $q) use ($rate): void {
                    $q->whereNotNull('rate_min')->where('rate_min', '<=', $rate);
                });
            });
        }

        if ($psk > 0) {
            $query->where(function (Builder $pskQuery) use ($psk): void {
                $pskQuery->where(function (Builder $q) use ($psk): void {
                    $q->whereNotNull('psk')->where('psk', '<=', $psk);
                })->orWhere(function (Builder $q) use ($psk): void {
                    $q->whereNotNull('psk_min')->where('psk_min', '<=', $psk);
                });
            });
        }
    }

    protected function buildFilterMeta(Builder $query): array
    {
        $items = (clone $query)->get([
            'max_amount',
            'term_days',
            'term_days_min',
            'term_no_interest',
            'term_no_interest_min',
            'rate',
            'rate_min',
            'psk',
            'psk_min',
        ]);

        $maxAmount = (float) $items->max(fn (Loan $loan): float => (float) ($loan->max_amount ?? 0));
        $maxTerm = (int) $items->max(fn (Loan $loan): int => max((int) ($loan->term_days ?? 0), (int) ($loan->term_days_min ?? 0)));
        $maxTermNoInterest = (int) $items->max(fn (Loan $loan): int => max((int) ($loan->term_no_interest ?? 0), (int) ($loan->term_no_interest_min ?? 0)));
        $maxRate = (float) $items->max(fn (Loan $loan): float => max((float) ($loan->rate ?? 0), (float) ($loan->rate_min ?? 0)));
        $maxPsk = (float) $items->max(fn (Loan $loan): float => max((float) ($loan->psk ?? 0), (float) ($loan->psk_min ?? 0)));

        return [
            'max_amount' => (int) ceil($maxAmount),
            'max_term' => max(0, $maxTerm),
            'max_term_no_interest' => max(0, $maxTermNoInterest),
            'max_rate' => round($maxRate, 2),
            'max_psk' => round($maxPsk, 2),
        ];
    }
}
