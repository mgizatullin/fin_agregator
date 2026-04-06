<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\SectionSetting;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        $section = SectionSetting::getOrCreateForType('blog');
        $articles = Article::query()
            ->with('category')
            ->where('is_published', true)
            ->latest('published_at')
            ->paginate(10);
        $categories = Category::query()
            ->where('is_active', true)
            ->withCount('articles')
            ->orderBy('name')
            ->get();
        $latestArticles = Article::query()
            ->with('category')
            ->where('is_published', true)
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('blog.index', [
            'articles' => $articles,
            'categories' => $categories,
            'latestArticles' => $latestArticles,
            'section' => $section,
            'seo_title' => $section->seo_title,
            'seo_description' => $section->seo_description,
            'title' => $section->title,
        ]);
    }

    public function category(string $slug): View
    {
        $category = Category::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $section = SectionSetting::getOrCreateForType('blog');
        $articles = Article::query()
            ->with('category')
            ->where('is_published', true)
            ->where('category_id', $category->id)
            ->latest('published_at')
            ->paginate(10);
        $categories = Category::query()
            ->where('is_active', true)
            ->withCount('articles')
            ->orderBy('name')
            ->get();
        $latestArticles = Article::query()
            ->with('category')
            ->where('is_published', true)
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('blog.index', [
            'articles' => $articles,
            'categories' => $categories,
            'latestArticles' => $latestArticles,
            'category' => $category,
            'section' => (object) [
                'title' => $category->name,
                'subtitle' => $category->description,
                'description' => null,
            ],
            'seo_title' => null,
            'seo_description' => null,
            'title' => $category->name,
        ]);
    }

    public function show(string $slug): View
    {
        $article = Article::with(['category', 'specialist'])
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $categories = Category::query()
            ->where('is_active', true)
            ->withCount('articles')
            ->orderBy('name')
            ->get();
        $latestArticles = Article::query()
            ->with('category')
            ->where('is_published', true)
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('blog.show', [
            'article' => $article,
            'categories' => $categories,
            'latestArticles' => $latestArticles,
            'seo_title' => $article->seo_title ?? $article->title,
            'seo_description' => $article->seo_description,
            'title' => $article->title,
        ]);
    }
}
