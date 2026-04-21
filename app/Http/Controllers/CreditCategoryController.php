<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesLoadMorePagination;
use App\Http\Helpers\SectionRouteResolver;
use App\Models\CreditCategory;
use App\Models\SectionSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CreditCategoryController extends Controller
{
    use HandlesLoadMorePagination;

    public function show(Request $request, string $slug, ?string $citySlug = null): View|Response
    {
        $city = SectionRouteResolver::resolveCity($citySlug);

        $category = CreditCategory::where('slug', $slug)->firstOrFail();
        $query = $category->credits()
            ->with(['bank', 'receiveMethods'])
            ->where('credits.is_active', true);

        if ($city) {
            $query->whereHas('bank', function (Builder $bankQuery) use ($city): void {
                $bankQuery->where('is_active', true)
                    ->where(function (Builder $q) use ($city): void {
                        $q->where('is_online_bank', true)
                            ->orWhereHas('branches', function (Builder $branchesQuery) use ($city): void {
                                $branchesQuery
                                    ->where('is_active', true)
                                    ->where('city_id', $city->id);
                            });
                    });
            });
        }

        $this->applyFilters($query, $request);

        $items = $query
            ->orderBy('credits.name')
            ->paginate(20)
            ->withQueryString();

        if ($response = $this->loadMoreResponse($request, $items, 'credits.partials.list-items', [
            'items' => $items,
        ])) {
            return $response;
        }

        $sectionSetting = SectionSetting::getOrCreateForType('credits');
        $serviceName = $sectionSetting->title ?? 'Кредиты';

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

        $base = 'kredity/'.$category->slug;

        return view('credits.category-show', array_merge(compact('category', 'items', 'section', 'city'), [
            'sectionIndexUrl' => $city ? url_section('kredity/'.$city->slug) : url_canonical(route('credits.index')),
            'sectionIndexTitle' => 'Кредиты',
            'seo_title' => $seoTitle,
            'seo_description' => $seoDescription,
            'title' => $title,
            'showCitySelect' => true,
            'citySelectBase' => $base,
            'faq_title' => $sectionSetting->faq_title,
            'faq_description' => $sectionSetting->faq_description,
            'faq_items' => $sectionSetting->faq_items ?? [],
        ], $city ? [] : ['redirectToCityIfStored' => true, 'sectionBaseForRedirect' => $base]));
    }

    protected function applyFilters(Builder|BelongsToMany $query, Request $request): void
    {
        $amount = (int) $request->integer('amount', 0);
        $term = (int) $request->integer('term', 0);
        $rate = $request->filled('rate') ? (float) $request->input('rate') : 0.0;
        $psk = $request->filled('psk') ? (float) $request->input('psk') : 0.0;
        $receiveMethods = collect((array) $request->input('receive_methods', []))
            ->map(fn ($value): int => (int) $value)
            ->filter()
            ->values();

        if ($amount > 0) {
            $query->where('credits.max_amount', '>=', $amount);
        }

        if ($term > 0) {
            $query->where('credits.term_months', '>=', $term);
        }

        if ($rate > 0) {
            $query->whereNotNull('credits.rate')
                ->where('credits.rate', '>', 0)
                ->where('credits.rate', '<=', $rate);
        }

        if ($psk > 0) {
            $query->whereNotNull('credits.psk')
                ->where('credits.psk', '>', 0)
                ->where('credits.psk', '<=', $psk);
        }

        if ($receiveMethods->isNotEmpty()) {
            $query->whereHas('receiveMethods', function (Builder $receiveMethodsQuery) use ($receiveMethods): void {
                $receiveMethodsQuery->whereIn('credit_receive_methods.id', $receiveMethods->all());
            });
        }
    }
}
