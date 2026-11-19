<?php

namespace App\Console\Commands;

use App\Mail\DailyStatisticsMail;
use App\Models\ArticleView;
use App\Models\Comment;
use App\Models\DailyReportDelivery;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailyStatistics extends Command
{
    protected $signature = 'stats:daily {--date= : Дата отчёта в формате YYYY-MM-DD} {--force : Отправить повторно для демонстрации}';

    protected $description = 'ЛР14: отправить модераторам статистику просмотров новостей и новых комментариев за день';

    public function handle(): int
    {
        $timezone = config('app.timezone', 'Europe/Moscow');
        $dateString = $this->option('date') ?: CarbonImmutable::now($timezone)->format('Y-m-d');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
            $this->error('Укажите дату в формате YYYY-MM-DD.');
            return self::FAILURE;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $dateString, $timezone);
            if ($date === false || $date->format('Y-m-d') !== $dateString) {
                throw new \InvalidArgumentException('Invalid date');
            }
        } catch (\Throwable) {
            $this->error('Некорректная календарная дата.');
            return self::FAILURE;
        }

        $from = $date->startOfDay();
        $to = $from->addDay();

        // Учитываем все успешные открытия опубликованных страниц.
        $views = ArticleView::query()
            ->where('viewed_at', '>=', $from)
            ->where('viewed_at', '<', $to)
            ->count();

        // Новые сохранённые комментарии, включая ожидающие модерации.
        $comments = Comment::query()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to)
            ->count();

        $moderators = User::query()
            ->whereHas('role', fn ($query) => $query->where('slug', Role::MODERATOR))
            ->get();

        if ($moderators->isEmpty()) {
            $this->warn('Модераторов нет: отправлять статистику некому.');
            return self::SUCCESS;
        }

        $sent = 0;
        $skipped = 0;
        $failures = 0;

        foreach ($moderators as $moderator) {
            $alreadySent = DailyReportDelivery::query()
                ->where('moderator_id', $moderator->id)
                ->whereDate('report_date', $dateString)
                ->exists();

            if ($alreadySent && ! $this->option('force')) {
                $skipped++;
                continue;
            }

            try {
                Mail::to($moderator->email)
                    ->send(new DailyStatisticsMail($dateString, $views, $comments));

                // Отмечаем отправленным только после успешной передачи
                // письма выбранному почтовому драйверу.
                DailyReportDelivery::query()->updateOrCreate(
                    ['moderator_id' => $moderator->id, 'report_date' => $dateString],
                    ['sent_at' => now()]
                );
                $sent++;
            } catch (\Throwable $exception) {
                report($exception);
                $this->error('Не удалось отправить отчёт: '.$moderator->email);
                $failures++;
            }
        }

        $this->info("За {$dateString}: просмотров {$views}, комментариев {$comments}. Отправлено: {$sent}; повторов пропущено: {$skipped}.");

        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }
}
