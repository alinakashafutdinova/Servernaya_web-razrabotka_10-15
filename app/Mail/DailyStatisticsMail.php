<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** ЛР №14: ежедневная статистика модератору. */
class DailyStatisticsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $reportDate,
        public readonly int $viewsCount,
        public readonly int $commentsCount,
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Статистика сайта за '.$this->reportDate)
            ->view('emails.daily-statistics');
    }
}
