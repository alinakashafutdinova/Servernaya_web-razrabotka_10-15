<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CommentPolicy
{
    public function create(User $user, Article $article): Response
    {
        return $article->is_published
            ? Response::allow()
            : Response::denyAsNotFound('Нельзя комментировать неопубликованную статью.');
    }

    public function update(User $user, Comment $comment): Response
    {
        return Response::deny('Редактирование чужих комментариев отключено. Модератор может принять или отклонить комментарий.');
    }

    public function delete(User $user, Comment $comment): Response
    {
        return Response::deny('Удаление комментариев через статью отключено. Используйте модерацию комментариев.');
    }
}
