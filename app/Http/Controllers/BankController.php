<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\SectionSetting;
use Illuminate\View\View;

class BankController extends Controller
{
    public function index(): View
    {
        $items = Bank::where('is_active', true)->orderBy('name')->get();
        $setting = SectionSetting::forType('banks');
        $section = (object) [
            'title' => $setting?->title ?: 'Банки',
            'subtitle' => $setting?->subtitle ?: '',
            'description' => $setting?->description ?: '',
        ];

        return view('banks.index', compact('items', 'section'));
    }
}
