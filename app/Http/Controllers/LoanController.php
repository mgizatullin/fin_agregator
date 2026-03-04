<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\SectionSetting;
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
}

