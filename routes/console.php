<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment('Делаю сайты с вниманием к деталям.');
})->purpose('Вывести короткое сообщение');


/**
 * ЛР №14. Laravel 13: расписание регистрируется в routes/console.php,
 * а не в устаревшем app/Console/Kernel.php.
 *
 * Учебный режим выполняет команду каждую минуту, но повторные письма за
 * ту же дату исключает таблица daily_report_deliveries.
 * В рабочем режиме отчёт формируется в 23:59 по времени сайта.
 */
Schedule::command('stats:daily')
    ->everyMinute()
    ->timezone(config('app.timezone', 'Europe/Moscow'))
    ->when(static fn (): bool =>
        config('daily_reports.demo_every_minute', false)
        || now(config('app.timezone', 'Europe/Moscow'))->format('H:i') === '23:59'
    )
    ->withoutOverlapping(10);
