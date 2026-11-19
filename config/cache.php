<?php

return [
    // В Laravel 13 основной параметр CACHE_STORE, CACHE_DRIVER оставлен
    // для совместимости с формулировкой лабораторной №13.
    'default' => env('CACHE_STORE', env('CACHE_DRIVER', 'database')),
    'stores' => [
        'array' => ['driver' => 'array', 'serialize' => false],
        'file' => ['driver' => 'file', 'path' => storage_path('framework/cache/data')],
        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CACHE_CONNECTION'),
            'table' => env('DB_CACHE_TABLE', 'cache'),
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
            'lock_table' => env('DB_CACHE_LOCK_TABLE', 'cache_locks'),
        ],
    ],
    'prefix' => env('CACHE_PREFIX', 'course_10_15_cache'),
    // В кеше лежат только массивы и скалярные значения, а не PHP-объекты.
    'serializable_classes' => false,
];
