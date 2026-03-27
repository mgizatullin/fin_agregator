<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesLoadMorePagination;
use App\Http\Helpers\SectionRouteResolver;
use App\Models\DepositCategory;
use App\Models\DepositCondition;
use App\Models\SectionSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class DepositCategoryController extends Controller
{
    use HandlesLoadMorePagination;

    public function show(Request $request, string $slug, ?string $citySlug = null): View|Response
    {
        $city = SectionRouteResolver::resolveCity($citySlug);

        $category = DepositCategory::where('slug', $slug)->firstOrFail();
        $baseQuery = $category->deposits()
            ->with(['bank', 'currencies.conditions'])
            ->where('deposits.is_active', true)
            ->select('deposits.*');

        $filterMeta = $this->buildFilterMeta(clone $baseQuery);

        $query = clone $baseQuery;
        $this->applyFilters($query, $request);

        $items = $query->orderBy('deposits.name')
            ->paginate(20)
            ->withQueryString();

        if ($response = $this->loadMoreResponse($request, $items, 'deposits.partials.list-items', [
            'items' => $items,
        ])) {
            return $response;
        }

        $sectionSetting = SectionSetting::getOrCreateForType('deposits');
        $serviceName = $sectionSetting->title ?? 'Вклады';

        $variables = [
            'service_name' => $serviceName,
            'category_name' => $category->title ?? '',
            'city' => $city ? $city->name : null,
            'city.g' => $city ? ($city->name_genitive ?? $city->name) : null,
            'city.p' => $city ? ($city->name_prepositional ?? $city->name) : null,
        ];

        $useTemplates = filled($category->h1_template) || filled($category->seo_title_template) || filled($category->seo_description_template);

        if ($useTemplates) {
            $title = filled($category->h1_template)
                ? SectionRouteResolver::parseSeoTemplate($category->h1_template, $variables)
                : ($category->title.($city ? ' в '.($city->name_prepositional ?? $city->name) : ''));
            $seoTitle = filled($category->seo_title_template)
                ? SectionRouteResolver::parseSeoTemplate($category->seo_title_template, $variables)
                : null;
            $seoDescription = filled($category->seo_description_template)
                ? SectionRouteResolver::parseSeoTemplate($category->seo_description_template, $variables)
                : SectionRouteResolver::sectionDescription($category->description, $city);
        } else {
            $title = $category->title.($city ? ' в '.$city->name : '');
            $seoTitle = null;
            $seoDescription = SectionRouteResolver::sectionDescription($category->description, $city);
        }

        $section = (object) [
            'title' => $title,
            'subtitle' => $category->subtitle ?? '',
            'description' => $category->description ?? '',
        ];

        $base = 'vklady/'.$category->slug;

        return view('deposits.category-show', array_merge(compact('category', 'items', 'section', 'city'), [
            'sectionIndexUrl' => $city ? url_section('vklady/'.$city->slug) : url_canonical(route('deposits.index')),
            'sectionIndexTitle' => 'Вклады',
            'seo_title' => $seoTitle,
            'seo_description' => $seoDescription,
            'title' => $title,
            'showCitySelect' => true,
            'citySelectBase' => $base,
            'filterMeta' => $filterMeta,
        ], $city ? [] : ['redirectToCityIfStored' => true, 'sectionBaseForRedirect' => $base]));
    }

    protected function applyFilters(Builder|BelongsToMany $query, Request $request): void
    {
        $amount = (float) $request->input('amount', 0);
        $term = (int) $request->integer('term', 0);
        $rate = (float) $request->input('rate', 0);
        $depositType = trim((string) $request->input('deposit_type', ''));

        if ($depositType !== '') {
            $query->where('deposits.deposit_type', $depositType);
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

    protected function buildFilterMeta(Builder|BelongsToMany $query): array
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
            ->whereNotNull('deposits.deposit_type')
            ->where('deposits.deposit_type', '!=', '')
            ->orderBy('deposits.deposit_type')
            ->pluck('deposits.deposit_type')
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
