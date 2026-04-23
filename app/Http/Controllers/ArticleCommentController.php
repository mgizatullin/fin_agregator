<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ArticleCommentController extends Controller
{
    public function store(Request $request, Article $article): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        ArticleComment::create([
            'article_id' => $article->id,
            'name' => $data['name'],
            'body' => $data['body'],
            'is_published' => false,
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 512),
        ]);

        return redirect()
            ->to(url_section('blog/' . $article->slug) . '#comments')
            ->with('status', 'Спасибо! Комментарий отправлен на модерацию.');
    }
}

