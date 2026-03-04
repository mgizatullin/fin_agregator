<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\SectionSetting;
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
}

