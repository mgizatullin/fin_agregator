<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CityDialogController extends Controller
{
    /**
     * Return HTML of the city selection modal (loaded by JS).
     * Query param "base" = section base for links (e.g. vklady, kredity/akcionnyi).
     * Only cities with population >= 150000 in the list; Moscow and SPb slugs for quick links.
     */
    public function __invoke(Request $request): View
    {
        $base = $request->input('base', '');
        $base = is_string($base) ? trim(preg_replace('#/+#', '/', '/' . $base), '/') : '';

        $cities = City::query()
            ->where('is_active', true)
            ->where('population', '>=', 150000)
            ->orderBy('name')
            ->get(['name', 'slug']);

        $groupedCities = $cities->groupBy(function (City $city): string {
            return mb_strtoupper(mb_substr($city->name, 0, 1));
        });

        $groupedCities = $groupedCities->sortKeys();

        $moscow = City::where('is_active', true)->where('name', 'Москва')->first();
        $spb = City::where('is_active', true)->where('name', 'Санкт-Петербург')->first();

        return view('components.city-modal', [
            'groupedCities' => $groupedCities,
            'moscowSlug' => $moscow ? $moscow->slug : null,
            'spbSlug' => $spb ? $spb->slug : null,
            'sectionBase' => $base,
        ]);
    }
}
