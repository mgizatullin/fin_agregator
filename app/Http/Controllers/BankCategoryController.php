<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesLoadMorePagination;
use App\Http\Helpers\SectionRouteResolver;
use App\Models\Bank;
use App\Models\BankCategory;
use App\Models\SectionSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class BankCategoryController extends Controller
{
    use HandlesLoadMorePagination;

    public function show(Request $request, string $slug, ?string $citySlug = null): View|Response
    {
        $city = SectionRouteResolver::resolveCity($citySlug);

        $category = BankCategory::where('slug', $slug)->firstOrFail();
        $items = $category->banks()
            ->where('banks.is_active', true)
            ->when(
                filled($city),
                fn ($query) => $query->where(function ($q) use ($city): void {
                    $q->where('banks.is_online_bank', true)
                        ->orWhereHas('branches', function ($branches) use ($city): void {
                            $branches
                                ->where('is_active', true)
                                ->where('city_id', $city->id);
                        });
                }),
            )
            ->withCount([
                'branches' => function ($branches) use ($city): void {
                    $branches->where('is_active', true);

                    if ($city) {
                        $branches->where('city_id', $city->id);
                    }
                },
            ])
            ->orderBy('banks.name')
            ->paginate(20)
            ->withQueryString();

        if ($response = $this->loadMoreResponse($request, $items, 'banks.partials.list-items', [
            'items' => $items,
            'variant' => 'category',
        ])) {
            return $response;
        }

        $sectionSetting = SectionSetting::getOrCreateForType('banks');
        $serviceName = $sectionSetting->title ?? 'Банки';

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
                : ($category->title . ($city ? ' в ' . ($city->name_prepositional ?? $city->name) : ''));
            $seoTitle = filled($category->seo_title_template)
                ? SectionRouteResolver::parseSeoTemplate($category->seo_title_template, $variables)
                : null;
            $seoDescription = filled($category->seo_description_template)
                ? SectionRouteResolver::parseSeoTemplate($category->seo_description_template, $variables)
                : SectionRouteResolver::sectionDescription($category->description, $city);
        } else {
            $title = $category->title . ($city ? ' в ' . $city->name : '');
            $seoTitle = null;
            $seoDescription = SectionRouteResolver::sectionDescription($category->description, $city);
        }

        $section = (object) [
            'title' => $title,
            'subtitle' => $category->subtitle ?? '',
            'description' => $category->description ?? '',
        ];

        $base = 'banki/' . $category->slug;
        return view('banks.category-show', array_merge(compact('category', 'items', 'section', 'city'), [
            'sectionIndexUrl' => $city ? url_section('banki/' . $city->slug) : url_canonical(route('banks.index')),
            'sectionIndexTitle' => 'Банки',
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
}
