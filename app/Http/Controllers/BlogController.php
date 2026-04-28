<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\Category;
use App\Models\SectionSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $section = SectionSetting::getOrCreateForType('blog');
        $articles = $this->articlesPage($request);
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

    public function category(Request $request, string $slug): View
    {
        $category = Category::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $section = SectionSetting::getOrCreateForType('blog');
        $articles = $this->articlesPage($request, $category);
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

        $comments = ArticleComment::query()
            ->where('article_id', $article->id)
            ->published()
            ->latest('id')
            ->get();

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
            'comments' => $comments,
            'seo_title' => $article->seo_title ?? $article->title,
            'seo_description' => $article->seo_description,
            'title' => $article->title,
        ]);
    }

    private function articlesPage(Request $request, ?Category $category = null): LengthAwarePaginator
    {
        $search = $request->input('search', '');
        $search = is_string($search) ? trim($search) : '';

        $query = Article::query()
            ->with('category')
            ->where('is_published', true)
            ->when($category, fn (Builder $query) => $query->where('category_id', $category->id))
            ->latest('published_at');

        if ($search === '') {
            return $query->paginate(10)->withQueryString();
        }

        $articles = $query
            ->get()
            ->filter(fn (Article $article) => $this->matchesAny(
                $search,
                $article->title,
                $article->excerpt,
                $article->content,
                $article->content_html,
                $article->category?->name,
            ))
            ->values();

        return $this->paginateCollection($articles, 10, $request);
    }

    private function paginateCollection(Collection $items, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );
    }

    private function matchesAny(string $search, mixed ...$values): bool
    {
        foreach ($values as $value) {
            if ($value !== null && mb_stripos(strip_tags((string) $value), $search, 0, 'UTF-8') !== false) {
                return true;
            }
        }

        return false;
    }
}
