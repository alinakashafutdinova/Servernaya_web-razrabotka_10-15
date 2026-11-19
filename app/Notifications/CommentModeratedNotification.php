<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CommentModeratedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly bool $approved,
        public readonly int $articleId,
        public readonly string $articleTitle,
        public readonly string $commentExcerpt,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'status' => $this->approved ? 'approved' : 'rejected',
            'article_id' => $this->articleId,
            'article_title' => $this->articleTitle,
            'comment_excerpt' => mb_strimwidth($this->commentExcerpt, 0, 180, '…'),
        ];
    }
}
