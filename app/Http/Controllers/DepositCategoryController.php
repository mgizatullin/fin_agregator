<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\DepositCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepositCategoryController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $category = DepositCategory::where('slug', $slug)->firstOrFail();
        $items = $category->deposits()
            ->with('bank')
            ->where('deposits.is_active', true)
            ->orderBy('deposits.name')
            ->get();

        $section = (object) [
            'title' => $category->title,
            'subtitle' => $category->subtitle ?? '',
            'description' => $category->description ?? '',
        ];

        return view('deposits.category-show', array_merge(compact('category', 'items', 'section'), [
            'sectionIndexUrl' => route('deposits.index'),
            'sectionIndexTitle' => 'Вклады',
        ]));
    }
}
