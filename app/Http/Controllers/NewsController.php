<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArticleRequest;
use App\Models\Article;
use App\Mail\ArticleCreatedMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(): View
    {
        $articles = Article::query()
            ->where('is_published', true)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(6);

        return view('news.index', ['articles' => $articles]);
    }

    public function show(Article $article): View
    {
        $this->authorize('view', $article);

        $relatedArticles = Article::query()
            ->where('is_published', true)
            ->where('id', '!=', $article->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        $comments = $article->comments()->where('is_approved', true)->with('user')->orderByDesc('created_at')->orderByDesc('id')->get();

        return view('news.show', compact('article', 'relatedArticles', 'comments'));
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

        try {
            $moderators = User::query()
                ->whereHas('role', fn ($query) => $query->where('slug', Role::MODERATOR))
                ->get();

            foreach ($moderators as $moderator) {
                Mail::to($moderator->email)->send(new ArticleCreatedMail($article));
            }
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('news.manage')
                ->with('status', 'Статья сохранена, но уведомление не отправлено. Проверьте настройки почты.');
        }

        return redirect()->route('news.manage')->with('status', 'Статья успешно добавлена.');
    }

    public function edit(Article $article): View
    {
        $this->authorize('update', $article);
        return view('news.edit', compact('article'));
    }

    public function update(ArticleRequest $request, Article $article): RedirectResponse
    {
        $this->authorize('update', $article);
        $data = $request->validated();
        $data['is_published'] = $request->boolean('is_published');
        $article->update($data);
        return redirect()->route('news.manage')->with('status', 'Изменения сохранены.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        $this->authorize('delete', $article);
        $article->delete();
        return redirect()->route('news.manage')->with('status', 'Статья удалена.');
    }
}
