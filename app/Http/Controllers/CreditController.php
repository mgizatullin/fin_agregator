<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesLoadMorePagination;
use App\Http\Helpers\SectionRouteResolver;
use App\Models\Credit;
use App\Models\CreditCategory;
use App\Models\SectionSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CreditController extends Controller
{
    use HandlesLoadMorePagination;

    public function index(Request $request, ?string $citySlug = null): View|Response
    {
        $city = SectionRouteResolver::resolveCity($citySlug);

        $items = Credit::with(['bank', 'receiveMethods'])
            ->where('is_active', true)
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        if ($response = $this->loadMoreResponse($request, $items, 'credits.partials.list-items', [
            'items' => $items,
        ])) {
            return $response;
        }

        $setting = SectionSetting::forType('credits');
        $section = (object) [
            'title' => $setting?->title ?: 'Кредиты',
            'subtitle' => $setting?->subtitle ?: '',
            'description' => $setting?->description ?: '',
        ];

        $categories = CreditCategory::orderBy('title')->get();

        if ($city) {
            $seo_title = filled($setting?->seo_title_template)
                ? SectionRouteResolver::parseTemplate($setting->seo_title_template, $section, $city)
                : ($setting?->seo_title ? $setting->seo_title . ' в ' . ($city->name_prepositional ?? $city->name) : null);
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

        return view('credits.index', [
            'items' => $items,
            'section' => $section,
            'categories' => $categories,
            'city' => $city,
            'seo_title' => $seo_title,
            'seo_description' => $seo_description,
            'title' => $title,
            'page_h1' => $page_h1,
            'page_content' => $page_content,
        ]);
    }

    /**
     * Show a single credit by slug (document page). No city in URL.
     */
    public function show(Request $request, string $slug): View
    {
        $credit = Credit::with(['bank', 'receiveMethods', 'reviews.bank'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $section = (object) [
            'title' => $credit->name . ($credit->bank ? ' — ' . $credit->bank->name : ''),
            'subtitle' => $credit->bank ? $credit->bank->name : '',
        ];

        return view('credits.show', array_merge(compact('credit', 'section'), [
            'sectionIndexUrl' => route('credits.index'),
            'sectionIndexTitle' => 'Кредиты',
            'seo_title' => null,
            'seo_description' => null,
            'title' => $section->title,
        ]));
    }
}
