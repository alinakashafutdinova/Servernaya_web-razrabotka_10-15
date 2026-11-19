<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArticleRequest;
use App\Models\Article;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use App\Jobs\VeryLongJob;
use App\Events\NewArticleEvent;
use App\Notifications\NewArticleNotification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NewsController extends Controller
{
    /**
     * ЛР №13: раздельное кеширование всех страниц публичной пагинации.
     * Храним только массивы атрибутов — это безопасно для Laravel 13.
     */
    public function index(Request $request): View
    {
        $page = max(1, (int) $request->query('page', 1));
        $version = Cache::rememberForever(
            'news.index.version',
            static fn (): string => bin2hex(random_bytes(8))
        );

        $payload = Cache::remember(
            "news.index.{$version}.page.{$page}",
            now()->addMinutes(10),
            static function () use ($page): array {
                $result = Article::query()
                    ->where('is_published', true)
                    ->orderByDesc('published_at')
                    ->orderByDesc('id')
                    ->paginate(6, ['*'], 'page', $page);

                return [
                    'items' => $result->getCollection()
                        ->map(static fn (Article $item): array => $item->getAttributes())
                        ->all(),
                    'total' => $result->total(),
                    'per_page' => $result->perPage(),
                    'page' => $result->currentPage(),
                ];
            }
        );

        $articles = new LengthAwarePaginator(
            Article::hydrate($payload['items']),
            $payload['total'],
            $payload['per_page'],
            $payload['page'],
            ['path' => $request->url(), 'pageName' => 'page']
        );

        return view('news.index', ['articles' => $articles]);
    }

    /**
     * ЛР №13: статья и одобренные комментарии сохраняются без срока жизни.
     * Проверка ArticlePolicy выполняется до чтения кеша, поэтому черновики
     * никогда не становятся публичными из-за кеширования.
     */
    public function show(Article $article): View
    {
        $this->authorize('view', $article);

        $payload = Cache::rememberForever(
            "news.show.{$article->id}",
            static function () use ($article): array {
                $approved = $article->comments()
                    ->where('is_approved', true)
                    ->with('user')
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->get();

                return [
                    'article' => $article->getAttributes(),
                    'comments' => $approved->map(static function (Comment $comment): array {
                        return [
                            'attributes' => $comment->getAttributes(),
                            'user' => [
                                'id' => $comment->user?->id ?? 0,
                                'name' => $comment->user?->name ?? 'Удалённый пользователь',
                            ],
                        ];
                    })->all(),
                ];
            }
        );

        $cachedArticle = Article::hydrate([$payload['article']])->first();
        $comments = Comment::hydrate(array_column($payload['comments'], 'attributes'));
        foreach ($comments as $index => $comment) {
            $user = User::hydrate([$payload['comments'][$index]['user']])->first();
            $comment->setRelation('user', $user);
        }

        // Похожие новости меняются при создании новых статей,
        // поэтому их выбираем отдельно, не оставляя вечный устаревший список.
        $relatedArticles = Article::query()
            ->where('is_published', true)
            ->where('id', '!=', $article->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        return view('news.show', [
            'article' => $cachedArticle,
            'relatedArticles' => $relatedArticles,
            'comments' => $comments,
        ]);
    }

    public function manage(): View
    {
        $this->authorize('viewManagement', Article::class);
        $articles = Article::query()->orderByDesc('id')->paginate(8);
        return view('news.manage', compact('articles'));
    }

    public function create(): View
    {
        $this->authorize('create', Article::class);
        return view('news.create');
    }

    public function store(ArticleRequest $request): RedirectResponse
    {
        $this->authorize('create', Article::class);
        $data = $request->validated();
        $data['is_published'] = $request->boolean('is_published');
        $article = Article::create($data);

        // Сброс версии делает недействительными все кешированные страницы /news,
        // включая ?page=2, ?page=3 и т. д.; старые ключи истекут по TTL.
        Cache::forget('news.index.version');

        if ($article->is_published) {
            $this->notifyReaders($article, $request->user()->id);
        }

        $mailQueued = true;

        try {
            VeryLongJob::dispatch($article->id);
        } catch (\Throwable $exception) {
            report($exception);
            $mailQueued = false;
        }

        // Онлайн-оповещение не зависит от успеха отправки электронного письма.
        // Событие публично: не показываем посетителям черновики.
        if ($article->is_published) {
            try {
                NewArticleEvent::dispatch($article->id, $article->title);
            } catch (\Throwable $exception) {
                // Не теряем опубликованную статью при сбое внешнего сервиса.
                report($exception);
            }
        }

        if (! $mailQueued) {
            return redirect()->route('news.manage')
                ->with('status', 'Статья сохранена, но почтовое уведомление не отправлено. Проверьте настройки почты.');
        }

        $message = config('queue.default') === 'database'
            ? 'Статья сохранена. Уведомление поставлено в очередь.'
            : 'Статья сохранена. Задание по отправке письма выполнено.';

        return redirect()->route('news.manage')->with('status', $message);
    }

    public function edit(Article $article): View
    {
        $this->authorize('update', $article);
        return view('news.edit', compact('article'));
    }

    public function update(ArticleRequest $request, Article $article): RedirectResponse
    {
        $this->authorize('update', $article);
        $wasPublished = $article->is_published;
        $data = $request->validated();
        $data['is_published'] = $request->boolean('is_published');
        $article->update($data);
        Cache::flush(); // ЛР №13: очистка всех страниц и карточек при изменении.

        // Публикация ранее скрытого черновика тоже является новой статьёй для читателя.
        if (! $wasPublished && $article->is_published) {
            $this->notifyReaders($article, $request->user()->id);
            NewArticleEvent::dispatch($article->id, $article->title);
        }

        return redirect()->route('news.manage')->with('status', 'Изменения сохранены.');
    }

    /**
     * Все зарегистрированные читатели, кроме автора текущего запроса.
     * Присутствие читателя на сайте не требуется: запись сохранится в БД.
     */
    private function notifyReaders(Article $article, int $authorId): void
    {
        $readers = User::query()
            ->whereHas('role', fn ($query) => $query->where('slug', Role::READER))
            ->whereKeyNot($authorId)
            ->get();

        if ($readers->isNotEmpty()) {
            Notification::send(
                $readers,
                new NewArticleNotification($article->id, $article->title)
            );
        }
    }

    public function destroy(Article $article): RedirectResponse
    {
        $this->authorize('delete', $article);
        $article->delete();
        Cache::flush(); // ЛР №13: удалённая статья и страницы списка больше не в кеше.
        return redirect()->route('news.manage')->with('status', 'Статья удалена.');
    }
}
