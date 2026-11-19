<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use App\Policies\ArticlePolicy;
use App\Policies\CommentPolicy;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Article::class => ArticlePolicy::class,
        Comment::class => CommentPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Модератор проходит проверку раньше отдельных политик.
        Gate::before(function (User $user, string $ability, array $arguments = []) {
            // Модератор может управлять публикацией комментария, но не изменять чужой текст.
            if (($arguments[0] ?? null) instanceof Comment && in_array($ability, ['update', 'delete'], true)) {
                return null;
            }

            return $user->isModerator() ? true : null;
        });

        Gate::define('manage-comments', fn (User $user) =>
            Response::deny('Управлять комментариями может только модератор.')
        );
    }
}
