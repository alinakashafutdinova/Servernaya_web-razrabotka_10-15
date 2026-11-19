<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'summary',
        'content',
        'published_at',
        'is_published',
    ];

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    protected $casts = [
        'published_at' => 'datetime',
        'is_published' => 'boolean',
    ];
}
