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

class BankController extends Controller
{
    use HandlesLoadMorePagination;

    public function index(Request $request, ?string $citySlug = null): View|Response
    {
        $city = SectionRouteResolver::resolveCity($citySlug);

        $items = Bank::withCount('branches')
            ->where('is_active', true)
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        if ($response = $this->loadMoreResponse($request, $items, 'banks.partials.list-items', [
            'items' => $items,
            'variant' => 'index',
        ])) {
            return $response;
        }
        $setting = SectionSetting::forType('banks');
        $section = (object) [
            'title' => $setting?->title ?: 'Банки',
            'subtitle' => $setting?->subtitle ?: '',
            'description' => $setting?->description ?: '',
        ];

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

        return view('banks.index', array_merge([
            'items' => $items,
            'section' => $section,
            'city' => $city,
            'seo_title' => $seo_title,
            'seo_description' => $seo_description,
            'title' => $title,
            'page_h1' => $page_h1,
            'page_content' => $page_content,
        ], $city ? [] : ['redirectToCityIfStored' => true, 'sectionBaseForRedirect' => 'banki']));
    }

    /**
     * Show a single bank by slug (material page). No city in URL.
     */
    public function show(Request $request, string $slug): View
    {
        $bank = Bank::with('reviews.bank')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $section = (object) [
            'title' => $bank->name,
            'subtitle' => $bank->license_number ? 'Лиц. ' . $bank->license_number : '',
        ];

        return view('banks.show', array_merge(compact('bank', 'section'), [
            'sectionIndexUrl' => route('banks.index'),
            'sectionIndexTitle' => 'Банки',
            'seo_title' => $bank->seo_title,
            'seo_description' => $bank->seo_description,
            'title' => $section->title,
        ]));
    }
}
