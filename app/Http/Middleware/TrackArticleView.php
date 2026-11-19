<?php

namespace App\Http\Middleware;

use App\Models\Article;
use App\Models\ArticleView;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** ЛР №14: считает только реально открытые и доступные страницы статей. */
class TrackArticleView
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $article = $request->route('article');

        if ($request->isMethod('GET')
            && $response->getStatusCode() === 200
            && $article instanceof Article) {
            ArticleView::create([
                'article_id' => $article->id,
                // Не сохраняем персональные и секретные значения query-string.
                'url' => '/'.ltrim($request->path(), '/'),
                'viewed_at' => now(),
            ]);
        }

        return $response;
    }
}
