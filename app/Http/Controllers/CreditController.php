<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesLoadMorePagination;
use App\Http\Helpers\SectionRouteResolver;
use App\Models\Credit;
use App\Models\CreditCategory;
use App\Models\SectionSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CreditController extends Controller
{
    use HandlesLoadMorePagination;

    public function index(Request $request, ?string $citySlug = null): View|Response
    {
        $city = SectionRouteResolver::resolveCity($citySlug);

        $query = Credit::with(['bank', 'receiveMethods'])
            ->where('is_active', true);

        $this->applyFilters($query, $request);

        $items = $query
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
            'title' => $credit->name.($credit->bank ? ' — '.$credit->bank->name : ''),
            'subtitle' => $credit->bank ? $credit->bank->name : '',
        ];

        return view('credits.show', array_merge(compact('credit', 'section'), [
            'sectionIndexUrl' => url_canonical(route('credits.index')),
            'sectionIndexTitle' => 'Кредиты',
            'seo_title' => null,
            'seo_description' => null,
            'title' => $section->title,
        ]));
    }

    protected function applyFilters(Builder $query, Request $request): void
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
            $query->where('max_amount', '>=', $amount);
        }

        if ($term > 0) {
            $query->where('term_months', '>=', $term);
        }

        if ($rate > 0) {
            $query->whereNotNull('rate')
                ->where('rate', '>', 0)
                ->where('rate', '<=', $rate);
        }

        if ($psk > 0) {
            $query->whereNotNull('psk')
                ->where('psk', '>', 0)
                ->where('psk', '<=', $psk);
        }

        if ($receiveMethods->isNotEmpty()) {
            $query->whereHas('receiveMethods', function (Builder $receiveMethodsQuery) use ($receiveMethods): void {
                $receiveMethodsQuery->whereIn('credit_receive_methods.id', $receiveMethods->all());
            });
        }
    }
}
