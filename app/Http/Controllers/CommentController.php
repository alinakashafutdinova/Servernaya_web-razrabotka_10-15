<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Comment;
use App\Notifications\CommentModeratedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function store(Request $request, Article $article): RedirectResponse
    {
        $this->authorize('create', [Comment::class, $article]);
        $data = $request->validate([
            'body' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'body.required' => 'Введите текст комментария.',
            'body.min' => 'Комментарий должен содержать не менее 3 символов.',
            'body.max' => 'Комментарий не должен превышать 1000 символов.',
        ]);
        $article->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);
        Cache::forget("news.show.{$article->id}");

        return redirect()->route('news.show', $article)->with('status', 'Комментарий отправлен и ожидает модерации.');
    }

    public function moderation(): View
    {
        Gate::authorize('manage-comments');
        $comments = Comment::query()
            ->where('is_approved', false)
            ->with(['user', 'article'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate(8);

        return view('comments.moderation', compact('comments'));
    }

    public function approve(Comment $comment): RedirectResponse
    {
        Gate::authorize('manage-comments');
        abort_if($comment->is_approved, 404);
        DB::transaction(function () use ($comment) {
            $comment->is_approved = true;
            $comment->save();
            $comment->user->notify(new CommentModeratedNotification(
                true, $comment->article_id, $comment->article->title, $comment->body
            ));
        });
        // Новые одобренные комментарии должны появляться сразу.
        Cache::forget("news.show.{$comment->article_id}");

        return redirect()->route('comments.moderation')->with('status', 'Комментарий опубликован. Автор получил уведомление в своём профиле.');
    }

    public function reject(Comment $comment): RedirectResponse
    {
        Gate::authorize('manage-comments');
        abort_if($comment->is_approved, 404);
        DB::transaction(function () use ($comment) {
            $comment->user->notify(new CommentModeratedNotification(
                false, $comment->article_id, $comment->article->title, $comment->body
            ));
            $comment->delete();
        });
        Cache::forget("news.show.{$comment->article_id}");

        return redirect()->route('comments.moderation')->with('status', 'Комментарий отклонён и удалён. Автор получил уведомление в своём профиле.');
    }

}
