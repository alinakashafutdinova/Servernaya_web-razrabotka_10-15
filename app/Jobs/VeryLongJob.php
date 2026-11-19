<?php

namespace App\Jobs;

use App\Mail\ArticleCreatedMail;
use App\Models\Article;
use App\Models\Role;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/** Laboratory #10: send the article-created email through Laravel Queue. */
class VeryLongJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $articleId)
    {
    }

    public function handle(): void
    {
        $article = Article::find($this->articleId);
        if ($article === null) {
            return;
        }

        $moderators = User::query()
            ->whereHas('role', fn ($query) => $query->where('slug', Role::MODERATOR))
            ->get();

        foreach ($moderators as $moderator) {
            Mail::to($moderator->email)->send(new ArticleCreatedMail($article));
        }
    }
}
