<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArticleRequest;
use App\Models\Article;
use App\Services\ArticleApiSideEffects;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ЛР №15: REST JSON-вариант NewsController (веб-страницы сохранены).
 * 200 GET/PUT/PATCH, 201 POST, 204 DELETE, 401/403/404/422 для ошибок.
 */
class ArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $articles = Article::query()
            ->where('is_published', true)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(6);

        return response()->json([
            'data' => $articles->getCollection()->map(
                fn (Article $article): array => $this->toArray($article)
            )->all(),
            'meta' => [
                'current_page' => $articles->currentPage(),
                'per_page' => $articles->perPage(),
                'last_page' => $articles->lastPage(),
                'total' => $articles->total(),
            ],
            'links' => [
                'first' => $articles->url(1),
                'last' => $articles->url($articles->lastPage()),
                'prev' => $articles->previousPageUrl(),
                'next' => $articles->nextPageUrl(),
            ],
        ]);
    }

    public function show(Article $article): JsonResponse
    {
        $this->authorize('view', $article);

        $article->load([
            'comments' => static fn ($query) => $query
                ->where('is_approved', true)
                ->with('user:id,name')
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
        ]);

        return response()->json([
            'data' => array_merge($this->toArray($article), [
                'comments' => $article->comments->map(static fn ($comment): array => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'created_at' => $comment->created_at?->toIso8601String(),
                    'author' => [
                        'id' => $comment->user?->id,
                        'name' => $comment->user?->name ?? 'Удалённый пользователь',
                    ],
                ])->all(),
            ]),
        ]);
    }

    public function manage(): JsonResponse
    {
        $this->authorize('viewManagement', Article::class);

        $articles = Article::query()->orderByDesc('id')->paginate(8);

        return response()->json([
            'data' => $articles->getCollection()->map(
                fn (Article $article): array => $this->toArray($article)
            )->all(),
            'meta' => [
                'current_page' => $articles->currentPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
                'last_page' => $articles->lastPage(),
            ],
        ]);
    }

    public function store(ArticleRequest $request, ArticleApiSideEffects $effects): JsonResponse
    {
        $data = $request->validated();
        $data['is_published'] = $request->boolean('is_published');

        $article = Article::create($data);
        $mailQueued = $effects->created($article, $request->user()->id);

        return response()->json([
            'message' => $mailQueued
                ? 'Статья сохранена. Задание на рассылку создано.'
                : 'Статья сохранена, но отправку email не удалось поставить в очередь.',
            'data' => $this->toArray($article),
        ], 201)->header('Location', route('api.articles.show', $article));
    }

    public function update(Request $request, Article $article, ArticleApiSideEffects $effects): JsonResponse
    {
        $this->authorize('update', $article);

        // PATCH допускает частичное обновление; PUT требует все основные поля.
        $partial = $request->isMethod('PATCH');
        $rule = $partial ? 'sometimes' : 'required';
        $data = $request->validate([
            'title' => [$rule, 'string', 'min:3', 'max:160'],
            'summary' => [$rule, 'string', 'min:10', 'max:500'],
            'content' => [$rule, 'string', 'min:10', 'max:20000'],
            'published_at' => [$rule, 'date_format:Y-m-d'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $wasPublished = $article->is_published;
        $article->update($data);
        $effects->updated($article, $wasPublished, $request->user()->id);

        return response()->json([
            'message' => 'Изменения сохранены.',
            'data' => $this->toArray($article->refresh()),
        ]);
    }

    public function destroy(Article $article, ArticleApiSideEffects $effects): JsonResponse
    {
        $this->authorize('delete', $article);

        $article->delete();
        $effects->deleted();

        return response()->json(null, 204);
    }

    private function toArray(Article $article): array
    {
        return [
            'id' => $article->id,
            'title' => $article->title,
            'summary' => $article->summary,
            'content' => $article->content,
            'published_at' => $article->published_at?->toDateString(),
            'is_published' => (bool) $article->is_published,
            'created_at' => $article->created_at?->toIso8601String(),
            'updated_at' => $article->updated_at?->toIso8601String(),
        ];
    }
}
