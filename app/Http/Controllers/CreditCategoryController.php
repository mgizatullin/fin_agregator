<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\CreditCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreditCategoryController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $category = CreditCategory::where('slug', $slug)->firstOrFail();
        $items = $category->credits()
            ->with('bank')
            ->where('credits.is_active', true)
            ->orderBy('credits.name')
            ->get();

        $section = (object) [
            'title' => $category->title,
            'subtitle' => $category->subtitle ?? '',
            'description' => $category->description ?? '',
        ];

        return view('credits.category-show', array_merge(compact('category', 'items', 'section'), [
            'sectionIndexUrl' => route('credits.index'),
            'sectionIndexTitle' => 'Кредиты',
        ]));
    }
}
