<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Лабораторная №12: сообщение читателю в личном кабинете о новой статье.
 */
class NewArticleNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $articleId,
        public readonly string $articleTitle,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'new_article',
            'article_id' => $this->articleId,
            'article_title' => $this->articleTitle,
            'message' => 'Опубликована новая статья',
        ];
    }
}
