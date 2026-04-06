<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesLoadMorePagination;
use App\Http\Helpers\SectionRouteResolver;
use App\Models\Loan;
use App\Models\LoanCategory;
use App\Models\SectionSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class LoanCategoryController extends Controller
{
    use HandlesLoadMorePagination;

    public function show(Request $request, string $slug, ?string $citySlug = null): View|Response
    {
        $city = SectionRouteResolver::resolveCity($citySlug);

        $category = LoanCategory::where('slug', $slug)->firstOrFail();
        $items = $category->loans()
            ->where('loans.is_active', true)
            ->orderBy('loans.name')
            ->paginate(20)
            ->withQueryString();

        if ($response = $this->loadMoreResponse($request, $items, 'loans.partials.list-items', [
            'items' => $items,
        ])) {
            return $response;
        }

        $sectionSetting = SectionSetting::getOrCreateForType('loans');
        $serviceName = $sectionSetting->title ?? 'Займы';

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

        $base = 'zaimy/' . $category->slug;
        return view('loans.category-show', array_merge(compact('category', 'items', 'section', 'city'), [
            'sectionIndexUrl' => $city ? url_section('zaimy/' . $city->slug) : url_canonical(route('loans.index')),
            'sectionIndexTitle' => 'Займы',
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
