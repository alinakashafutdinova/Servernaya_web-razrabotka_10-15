<?php

return [
    // Laravel 13 uses BROADCAST_CONNECTION; BROADCAST_DRIVER remains supported
    // for compatibility with the laboratory handout.
    'default' => env('BROADCAST_CONNECTION', env('BROADCAST_DRIVER', 'log')),
    'connections' => [
        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER', 'eu'),
                'useTLS' => true,
            ],
        ],
        'log' => ['driver' => 'log'],
        'null' => ['driver' => 'null'],
    ],
];
