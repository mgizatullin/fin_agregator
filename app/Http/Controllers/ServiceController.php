<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Services\CbrRatesService;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function show(string $slug, CbrRatesService $cbrRates): View
    {
        $service = Service::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        if ($service->type === Service::TYPE_CURRENCY_RATES) {
            $pageData = $cbrRates->getPopularRatesForPage();

            return view('services.currency-rates', [
                'service' => $service,
                'pageData' => $pageData,
                'seo_title' => $service->seo_title ?: $service->title,
                'seo_description' => $service->seo_description,
                'title' => $service->title,
            ]);
        }

        return view('services.show', [
            'service' => $service,
            'depositKeyRate' => (float) config('services.deposit_calculator.cb_key_rate_percent', 18),
            'seo_title' => $service->seo_title ?: $service->title,
            'seo_description' => $service->seo_description,
            'title' => $service->title,
        ]);
    }
}
