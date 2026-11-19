<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ArticlePolicy
{
    public function viewAny(?User $user): Response
    {
        return Response::allow();
    }

    public function view(?User $user, Article $article): Response
    {
        return $article->is_published
            ? Response::allow()
            : Response::denyAsNotFound('Черновик недоступен для просмотра.');
    }

    public function viewManagement(User $user): Response
    {
        return Response::deny('Управление статьями доступно только модератору.');
    }

    public function create(User $user): Response
    {
        return Response::deny('Создание статей доступно только модератору.');
    }

    public function update(User $user, Article $article): Response
    {
        return Response::deny('Редактировать статьи может только модератор.');
    }

    public function delete(User $user, Article $article): Response
    {
        return Response::deny('Удалять статьи может только модератор.');
    }
}
