<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\CardCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CardCategoryController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $category = CardCategory::where('slug', $slug)->firstOrFail();
        $items = $category->cards()
            ->with('bank')
            ->where('cards.is_active', true)
            ->orderBy('cards.name')
            ->get();

        $section = (object) [
            'title' => $category->title,
            'subtitle' => $category->subtitle ?? '',
            'description' => $category->description ?? '',
        ];

        return view('cards.category-show', array_merge(compact('category', 'items', 'section'), [
            'sectionIndexUrl' => route('cards.index'),
            'sectionIndexTitle' => 'Кредитные карты',
        ]));
    }
}
