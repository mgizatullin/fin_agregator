<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\CreditCategory;
use App\Models\SectionSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreditController extends Controller
{
    public function index(): View
    {
        $items = Credit::with('bank')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $setting = SectionSetting::forType('credits');
        $section = (object) [
            'title' => $setting?->title ?: 'Кредиты',
            'subtitle' => $setting?->subtitle ?: '',
            'description' => $setting?->description ?: '',
        ];

        return view('credits.index', compact('items', 'section'));
    }

    /**
     * Show a single credit by slug (document page).
     */
    public function show(Request $request, string $slug): View
    {
        $credit = Credit::with('bank')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $section = (object) [
            'title' => $credit->name . ($credit->bank ? ' — ' . $credit->bank->name : ''),
            'subtitle' => $credit->bank ? $credit->bank->name : '',
        ];

        return view('credits.show', array_merge(compact('credit', 'section'), [
            'sectionIndexUrl' => route('credits.index'),
            'sectionIndexTitle' => 'Кредиты',
        ]));
    }
}

