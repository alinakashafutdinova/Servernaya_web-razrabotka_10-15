<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    protected $fillable = ['body', 'user_id'];

    protected $casts = [
        'article_id' => 'integer',
        'user_id' => 'integer',
        'is_approved' => 'boolean',
    ];

    /**
     * Сброс кеша комментариев при любом изменении модели, даже если
     * одобрение выполнено напрямую через Eloquent вне контроллера.
     */
    protected static function booted(): void
    {
        static::saved(static function (self $comment): void {
            Cache::forget("news.show.{$comment->article_id}");
        });
        static::deleted(static function (self $comment): void {
            Cache::forget("news.show.{$comment->article_id}");
        });
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
