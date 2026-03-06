<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CityDialogController extends Controller
{
    /**
     * Return HTML of the city selection modal (loaded by JS, not in layout).
     */
    public function __invoke(Request $request): View
    {
        $cities = City::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['name', 'slug']);

        $groupedCities = $cities->groupBy(function (City $city): string {
            return mb_strtoupper(mb_substr($city->name, 0, 1));
        });

        $groupedCities = $groupedCities->sortKeys();

        return view('components.city-modal', [
            'groupedCities' => $groupedCities,
        ]);
    }
}
