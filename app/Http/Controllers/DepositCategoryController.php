<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesLoadMorePagination;
use App\Http\Helpers\SectionRouteResolver;
use App\Models\Deposit;
use App\Models\DepositCategory;
use App\Models\SectionSetting;
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
        $items = $category->deposits()
            ->with('bank')
            ->where('deposits.is_active', true)
            ->orderBy('deposits.name')
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

        $base = 'vklady/' . $category->slug;
        return view('deposits.category-show', array_merge(compact('category', 'items', 'section', 'city'), [
            'sectionIndexUrl' => $city ? url_section('vklady/' . $city->slug) : url_canonical(route('deposits.index')),
            'sectionIndexTitle' => 'Вклады',
            'seo_title' => $seoTitle,
            'seo_description' => $seoDescription,
            'title' => $title,
            'showCitySelect' => true,
            'citySelectBase' => $base,
        ], $city ? [] : ['redirectToCityIfStored' => true, 'sectionBaseForRedirect' => $base]));
    }
}
