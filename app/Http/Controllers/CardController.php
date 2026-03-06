<?php

namespace App\Http\Controllers;

use App\Http\Helpers\SectionRouteResolver;
use App\Models\Card;
use App\Models\CardCategory;
use App\Models\SectionSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CardController extends Controller
{
    public function index(Request $request, ?string $citySlug = null): View|Response
    {
        $city = SectionRouteResolver::resolveCity($citySlug);

        $cards = Card::with('bank')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $categories = CardCategory::orderBy('title')->get();
        $setting = SectionSetting::forType('cards');
        $section = (object) [
            'title' => $setting?->title ?: 'Кредитные карты',
            'subtitle' => $setting?->subtitle ?? '',
            'description' => $setting?->description ?? '',
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

        return view('cards.index', [
            'cards' => $cards,
            'categories' => $categories,
            'section' => $section,
            'city' => $city,
            'seo_title' => $seo_title,
            'seo_description' => $seo_description,
            'title' => $title,
            'page_h1' => $page_h1,
            'page_content' => $page_content,
        ]);
    }

    /**
     * Show a single card by slug. No city in URL.
     */
    public function show(Request $request, string $slug): View
    {
        $card = Card::with('bank')->where('slug', $slug)->where('is_active', true)->firstOrFail();
        return view('cards.show', [
            'card' => $card,
            'seo_title' => null,
            'seo_description' => null,
            'title' => $card->name,
        ]);
    }
}
