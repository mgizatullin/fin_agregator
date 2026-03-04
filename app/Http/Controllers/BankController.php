<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\SectionSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BankController extends Controller
{
    public function index(): View
    {
        $items = Bank::withCount('branches')->where('is_active', true)->orderBy('name')->get();
        $setting = SectionSetting::forType('banks');
        $section = (object) [
            'title' => $setting?->title ?: 'Банки',
            'subtitle' => $setting?->subtitle ?: '',
            'description' => $setting?->description ?: '',
        ];

        return view('banks.index', compact('items', 'section'));
    }

    /**
     * Show a single bank by slug (material page).
     */
    public function show(Request $request, string $slug): View
    {
        $bank = Bank::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $section = (object) [
            'title' => $bank->name,
            'subtitle' => $bank->license_number ? 'Лиц. ' . $bank->license_number : '',
        ];

        return view('banks.show', array_merge(compact('bank', 'section'), [
            'sectionIndexUrl' => route('banks.index'),
            'sectionIndexTitle' => 'Банки',
        ]));
    }
}
