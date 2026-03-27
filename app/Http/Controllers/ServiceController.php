<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\Deposit;
use App\Models\Service;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function show(string $slug): View
    {
        $service = Service::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $credit = $this->sampleCredit();
        $deposit = $this->sampleDeposit();

        return view('services.show', [
            'service' => $service,
            'credit' => $credit,
            'deposit' => $deposit,
            'seo_title' => $service->seo_title ?: $service->title,
            'seo_description' => $service->seo_description,
            'title' => $service->title,
        ]);
    }

    private function sampleCredit(): Credit
    {
        return new Credit([
            'min_amount' => 100_000,
            'max_amount' => 5_000_000,
            'rate_min' => 12,
            'rate' => 12,
            'term_months' => 12,
            'min_term_months' => 1,
            'max_term_months' => 60,
        ]);
    }

    private function sampleDeposit(): ?Deposit
    {
        return Deposit::query()
            ->where('is_active', true)
            ->whereHas('currencies.conditions', fn ($q) => $q->where('is_active', true))
            ->with(['currencies.conditions'])
            ->first();
    }
}
