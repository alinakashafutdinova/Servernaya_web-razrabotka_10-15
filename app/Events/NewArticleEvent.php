<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ЛР №11. Оповещение посетителей о новой опубликованной статье.
 * В публичный канал отправляем только безопасные открытые данные.
 */
class NewArticleEvent implements ShouldBroadcast, ShouldRescue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $articleId,
        public string $articleTitle,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new Channel('articles')];
    }

    public function broadcastAs(): string
    {
        return 'NewArticleEvent';
    }

    public function broadcastWith(): array
    {
        return [
            'article' => [
                'id' => $this->articleId,
                'title' => $this->articleTitle,
                'url' => route('news.show', ['article' => $this->articleId], false),
            ],
        ];
    }
}
