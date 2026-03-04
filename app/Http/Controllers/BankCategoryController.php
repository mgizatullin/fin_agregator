<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BankCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BankCategoryController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $category = BankCategory::where('slug', $slug)->firstOrFail();
        $items = $category->banks()
            ->where('banks.is_active', true)
            ->orderBy('banks.name')
            ->get();

        $section = (object) [
            'title' => $category->title,
            'subtitle' => $category->subtitle ?? '',
            'description' => $category->description ?? '',
        ];

        return view('banks.category-show', array_merge(compact('category', 'items', 'section'), [
            'sectionIndexUrl' => route('banks.index'),
            'sectionIndexTitle' => 'Банки',
        ]));
    }
}
