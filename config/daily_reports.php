<?php

return [
    // Демо: запуск из schedule:run сразу, но лишь один отчёт за день/модератора.
    // В рабочей среде отчёт формируется в 23:59 по времени сайта.
    'demo_every_minute' => (bool) env('DAILY_REPORT_DEMO_EVERY_MINUTE', false),
];
