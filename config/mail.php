<?php

return [
    'default' => env('MAIL_MAILER', 'log'),
    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME', 'smtps'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', 'smtp.mail.ru'),
            'port' => env('MAIL_PORT', 465),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => 20,
        ],
        'log' => ['transport' => 'log', 'channel' => env('MAIL_LOG_CHANNEL')],
        'array' => ['transport' => 'array'],
    ],
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@example.test'),
        'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Веб-лаборатория')),
    ],
];
