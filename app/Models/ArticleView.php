<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Один успешный HTTP GET страницы новости = одна запись просмотра.
 * Не храним IP, cookie, query-string и данные пользователя.
 */
class ArticleView extends Model
{
    public $timestamps = false;

    protected $fillable = ['article_id', 'url', 'viewed_at'];

    protected $casts = [
        'article_id' => 'integer',
        'viewed_at' => 'datetime',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
