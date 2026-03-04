<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\SectionSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoanController extends Controller
{
    public function index(): View
    {
        $items = Loan::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $setting = SectionSetting::forType('loans');
        $section = (object) [
            'title' => $setting?->title ?: 'Займы',
            'subtitle' => $setting?->subtitle ?: '',
            'description' => $setting?->description ?: '',
        ];

        return view('loans.index', compact('items', 'section'));
    }

    /**
     * Show a single loan by slug (material page).
     */
    public function show(Request $request, string $slug): View
    {
        $loan = Loan::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $section = (object) [
            'title' => $loan->name . ($loan->company_name ? ' — ' . $loan->company_name : ''),
            'subtitle' => $loan->company_name ?? '',
        ];

        return view('loans.show', array_merge(compact('loan', 'section'), [
            'sectionIndexUrl' => route('loans.index'),
            'sectionIndexTitle' => 'Займы',
        ]));
    }
}

