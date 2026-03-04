<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\SectionSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CardController extends Controller
{
    public function index(): View
    {
        $cards = Card::with('bank')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $categories = collect();
        $setting = SectionSetting::forType('cards');
        $section = (object) [
            'title' => $setting?->title ?: 'Кредитные карты',
            'subtitle' => $setting?->subtitle ?? '',
            'description' => $setting?->description ?? '',
        ];

        return view('cards.index', compact('cards', 'categories', 'section'));
    }

    public function show(Request $request, string $slug): View|\Illuminate\Http\Response
    {
        $card = Card::with('bank')->where('slug', $slug)->where('is_active', true)->firstOrFail();
        return view('cards.show', compact('card'));
    }
}
