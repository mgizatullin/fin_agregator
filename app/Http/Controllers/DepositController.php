<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesLoadMorePagination;
use App\Http\Helpers\SectionRouteResolver;
use App\Models\Bank;
use App\Models\Deposit;
use App\Models\DepositCategory;
use App\Models\SectionSetting;
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

        $items = Deposit::with(['bank', 'currencies.conditions'])
            ->where('is_active', true)
            ->orderBy('name')
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

        $section = (object) [
            'title' => $deposit->name . ($deposit->bank ? ' — ' . $deposit->bank->name : ''),
            'subtitle' => $deposit->bank ? $deposit->bank->name : '',
        ];

        $banks = Bank::where('is_active', true)->orderBy('name')->get();

        return view('deposits.show', array_merge(compact('deposit', 'section', 'banks'), [
            'sectionIndexUrl' => url_canonical(route('deposits.index')),
            'sectionIndexTitle' => 'Вклады',
            'seo_title' => null,
            'seo_description' => null,
            'title' => $section->title,
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
}
