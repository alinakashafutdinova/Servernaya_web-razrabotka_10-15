<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;

/** Ручной запуск отчёта модератором, в том числе на InfinityFree без cron. */
class DailyStatisticsController extends Controller
{
    public function send(): RedirectResponse
    {
        $this->authorize('viewManagement', Article::class);

        $exit = Artisan::call('stats:daily');

        return redirect()->route('news.manage')->with('status',
            $exit === 0
                ? 'Команда отчёта выполнена. Письмо отправлено или уже было отправлено сегодня. При MAIL_MAILER=log проверьте storage/logs/laravel.log.'
                : 'Не удалось отправить ежедневный отчёт. Проверьте настройки почты и журнал Laravel.'
        );
    }
}
