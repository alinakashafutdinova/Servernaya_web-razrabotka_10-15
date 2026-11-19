<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Фиксирует успешную отправку ежедневного отчёта каждому модератору. */
class DailyReportDelivery extends Model
{
    public $timestamps = false;

    protected $fillable = ['moderator_id', 'report_date', 'sent_at'];

    protected $casts = [
        'report_date' => 'date',
        'sent_at' => 'datetime',
    ];

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }
}
