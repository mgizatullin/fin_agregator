<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesLoadMorePagination;
use App\Http\Helpers\SectionRouteResolver;
use App\Models\Bank;
use App\Models\Branch;
use App\Models\City;
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
            ->orderByDesc('branches_count')
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
        $bank = Bank::with([
            'reviews.bank',
            'deposits',
            'cards',
            'credits',
            'branches',
        ])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $availableCities = $this->getBankAvailableCities($bank->id);
        $branchesCountAll = Branch::query()->where('bank_id', $bank->id)->where('is_active', true)->count();

        $section = (object) [
            'title' => $bank->name,
            'subtitle' => null,
        ];

        return view('banks.show', array_merge(compact('bank', 'section'), [
            'sectionIndexUrl' => url_canonical(route('banks.index')),
            'sectionIndexTitle' => 'Банки',
            'seo_title' => $bank->seo_title,
            'seo_description' => $bank->seo_description,
            'title' => $section->title,
            'availableCities' => $availableCities,
            'currentCity' => null,
            'city' => null,
            'branchesCount' => $branchesCountAll,
            'branchesCountAll' => $branchesCountAll,
            'branchesCountCity' => null,
        ]));
    }

    /**
     * Show a single bank by slug, scoped to a city that has branches.
     */
    public function showCity(Request $request, string $slug, string $citySlug): View
    {
        $bank = Bank::with([
            'reviews.bank',
            'deposits',
            'cards',
            'credits',
            'branches',
        ])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $availableCities = $this->getBankAvailableCities($bank->id);
        $city = $availableCities->firstWhere('slug', $citySlug);

        if (! $city) {
            abort(404);
        }

        $branchesCountAll = Branch::query()->where('bank_id', $bank->id)->where('is_active', true)->count();
        $branchesCountCity = Branch::query()
            ->where('bank_id', $bank->id)
            ->where('is_active', true)
            ->where('city_id', $city->id)
            ->count();

        $cityTitlePart = $city->name_prepositional ?? $city->name;

        $section = (object) [
            'title' => $bank->name.' в '.$cityTitlePart,
            'subtitle' => null,
        ];

        return view('banks.show', array_merge(compact('bank', 'section'), [
            'sectionIndexUrl' => url_canonical(route('banks.index')),
            'sectionIndexTitle' => 'Банки',
            'seo_title' => $bank->seo_title,
            'seo_description' => $bank->seo_description,
            'title' => $section->title,
            'availableCities' => $availableCities,
            'currentCity' => $city,
            'city' => $city,
            'branchesCount' => $branchesCountCity,
            'branchesCountAll' => $branchesCountAll,
            'branchesCountCity' => $branchesCountCity,
        ]));
    }

    public function branchesCity(Request $request, string $slug, string $citySlug): View
    {
        $city = City::query()->where('slug', $citySlug)->where('is_active', true)->firstOrFail();

        $bank = Bank::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $availableCities = $this->getBankAvailableCities($bank->id);
        if (! $availableCities->firstWhere('slug', $citySlug)) {
            abort(404);
        }

        $branches = Branch::query()
            ->where('bank_id', $bank->id)
            ->where('is_active', true)
            ->where('city_id', $city->id)
            ->orderByRaw('CASE WHEN latitude IS NULL OR longitude IS NULL THEN 1 ELSE 0 END')
            ->orderBy('id')
            ->get();

        $bank->setRelation('branches', $branches);

        $breadcrumbs = [
            ['url' => url('/'), 'label' => 'Главная'],
            ['url' => url_section('banki'), 'label' => 'Банки'],
            ['url' => url_section('banki/'.$bank->slug), 'label' => $bank->name],
            ['label' => 'Отделения'],
        ];

        $cityTitlePart = $city->name_prepositional ?? $city->name;

        return view('banks.branches', [
            'bank' => $bank,
            'breadcrumbs' => $breadcrumbs,
            'title' => 'Отделения '.$bank->name.' в '.$cityTitlePart,
            'currentCity' => $city,
            'availableCities' => $availableCities,
        ]);
    }

    protected function getBankAvailableCities(int $bankId)
    {
        return City::query()
            ->select('cities.*')
            ->join('branches', 'branches.city_id', '=', 'cities.id')
            ->where('branches.bank_id', $bankId)
            ->where('branches.is_active', true)
            ->whereNotNull('branches.city_id')
            ->distinct()
            ->orderBy('cities.name')
            ->get();
    }

    /**
     * Show bank reviews page
     */
    public function reviews(Request $request, string $slug): View
    {
        $bank = Bank::with('reviews.bank')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $breadcrumbs = [
            ['url' => url('/'), 'label' => 'Главная'],
            ['url' => url_section('banki'), 'label' => 'Банки'],
            ['url' => url_section('banki/'.$bank->slug), 'label' => $bank->name],
            ['label' => 'Отзывы'],
        ];

        return view('banks.reviews', [
            'bank' => $bank,
            'breadcrumbs' => $breadcrumbs,
            'title' => 'Отзывы о '.$bank->name,
            'seo_title' => 'Отзывы о '.$bank->name,
        ]);
    }

    /**
     * Show bank branches page
     */
    public function branches(Request $request, string $slug): View
    {
        $bank = Bank::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $branches = Branch::query()
            ->where('bank_id', $bank->id)
            ->where('is_active', true)
            ->orderByRaw('CASE WHEN latitude IS NULL OR longitude IS NULL THEN 1 ELSE 0 END')
            ->orderBy('id')
            ->get();

        $bank->setRelation('branches', $branches);

        $breadcrumbs = [
            ['url' => url('/'), 'label' => 'Главная'],
            ['url' => url_section('banki'), 'label' => 'Банки'],
            ['url' => url_section('banki/'.$bank->slug), 'label' => $bank->name],
            ['label' => 'Отделения'],
        ];

        return view('banks.branches', [
            'bank' => $bank,
            'breadcrumbs' => $breadcrumbs,
            'title' => 'Отделения '.$bank->name,
            'seo_title' => 'Отделения '.$bank->name,
            'currentCity' => null,
            'availableCities' => $this->getBankAvailableCities($bank->id),
        ]);
    }

    /**
     * Show bank deposits page
     */
    public function deposits(Request $request, string $slug): View
    {
        $bank = Bank::with('deposits')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $breadcrumbs = [
            ['url' => url('/'), 'label' => 'Главная'],
            ['url' => url_section('banki'), 'label' => 'Банки'],
            ['url' => url_section('banki/'.$bank->slug), 'label' => $bank->name],
            ['label' => 'Вклады'],
        ];

        return view('banks.deposits', [
            'bank' => $bank,
            'breadcrumbs' => $breadcrumbs,
            'title' => 'Вклады '.$bank->name,
            'seo_title' => 'Вклады '.$bank->name,
        ]);
    }

    /**
     * Show bank cards page
     */
    public function cards(Request $request, string $slug): View
    {
        $bank = Bank::with('cards')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $breadcrumbs = [
            ['url' => url('/'), 'label' => 'Главная'],
            ['url' => url_section('banki'), 'label' => 'Банки'],
            ['url' => url_section('banki/'.$bank->slug), 'label' => $bank->name],
            ['label' => 'Карты'],
        ];

        return view('banks.cards', [
            'bank' => $bank,
            'breadcrumbs' => $breadcrumbs,
            'title' => 'Карты '.$bank->name,
            'seo_title' => 'Карты '.$bank->name,
        ]);
    }

    /**
     * Show bank credits page
     */
    public function credits(Request $request, string $slug): View
    {
        $bank = Bank::with('credits')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $breadcrumbs = [
            ['url' => url('/'), 'label' => 'Главная'],
            ['url' => url_section('banki'), 'label' => 'Банки'],
            ['url' => url_section('banki/'.$bank->slug), 'label' => $bank->name],
            ['label' => 'Кредиты'],
        ];

        return view('banks.credits', [
            'bank' => $bank,
            'breadcrumbs' => $breadcrumbs,
            'title' => 'Кредиты '.$bank->name,
            'seo_title' => 'Кредиты '.$bank->name,
        ]);
    }
}
