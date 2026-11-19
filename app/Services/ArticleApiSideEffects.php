<?php

namespace App\Services;

use App\Events\NewArticleEvent;
use App\Jobs\VeryLongJob;
use App\Models\Article;
use App\Models\Role;
use App\Models\User;
use App\Notifications\NewArticleNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

/** API использует те же побочные действия, что веб-контроллер №10–13. */
class ArticleApiSideEffects
{
    public function created(Article $article, int $authorId): bool
    {
        Cache::forget('news.index.version');

        if ($article->is_published) {
            $this->notifyReaders($article, $authorId);
        }

        try {
            VeryLongJob::dispatch($article->id);
            $mailQueued = true;
        } catch (\Throwable $e) {
            report($e);
            $mailQueued = false;
        }

        if ($article->is_published) {
            $this->broadcast($article);
        }

        return $mailQueued;
    }

    public function updated(Article $article, bool $wasPublished, int $authorId): void
    {
        Cache::flush();

        if (! $wasPublished && $article->is_published) {
            $this->notifyReaders($article, $authorId);
            $this->broadcast($article);
        }
    }

    public function deleted(): void
    {
        Cache::flush();
    }

    private function notifyReaders(Article $article, int $authorId): void
    {
        User::query()
            ->whereHas('role', static fn ($query) => $query->where('slug', Role::READER))
            ->whereKeyNot($authorId)
            ->chunkById(100, static function ($readers) use ($article): void {
                Notification::send(
                    $readers,
                    new NewArticleNotification($article->id, $article->title)
                );
            });
    }

    private function broadcast(Article $article): void
    {
        try {
            NewArticleEvent::dispatch($article->id, $article->title);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
