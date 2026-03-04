<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\SectionSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepositController extends Controller
{
    public function index(): View
    {
        $items = Deposit::with('bank')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $setting = SectionSetting::forType('deposits');
        $section = (object) [
            'title' => $setting?->title ?: 'Вклады',
            'subtitle' => $setting?->subtitle ?: '',
            'description' => $setting?->description ?: '',
        ];

        return view('deposits.index', compact('items', 'section'));
    }

    /**
     * Show a single deposit by slug (material page).
     */
    public function show(Request $request, string $slug): View
    {
        $deposit = Deposit::with('bank')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $section = (object) [
            'title' => $deposit->name . ($deposit->bank ? ' — ' . $deposit->bank->name : ''),
            'subtitle' => $deposit->bank ? $deposit->bank->name : '',
        ];

        return view('deposits.show', array_merge(compact('deposit', 'section'), [
            'sectionIndexUrl' => route('deposits.index'),
            'sectionIndexTitle' => 'Вклады',
        ]));
    }
}

