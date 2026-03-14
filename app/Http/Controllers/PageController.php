<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(string $slug): View
    {
        $page = Page::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return view('pages.show', [
            'page' => $page,
            'seo_title' => $page->seo_title,
            'seo_description' => $page->seo_description,
            'title' => $page->title,
        ]);
    }
}
