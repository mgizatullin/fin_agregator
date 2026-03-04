<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoanCategoryController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $category = LoanCategory::where('slug', $slug)->firstOrFail();
        $items = $category->loans()
            ->where('loans.is_active', true)
            ->orderBy('loans.name')
            ->get();

        $section = (object) [
            'title' => $category->title,
            'subtitle' => $category->subtitle ?? '',
            'description' => $category->description ?? '',
        ];

        return view('loans.category-show', array_merge(compact('category', 'items', 'section'), [
            'sectionIndexUrl' => route('loans.index'),
            'sectionIndexTitle' => 'Займы',
        ]));
    }
}
