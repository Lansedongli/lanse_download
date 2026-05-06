<?php

return [
    'default' => env('CACHE_DRIVER', 'file'),
    'stores'  => [
        'file' => [
            'type'   => 'File',
            'path'   => runtime_path('cache'),
            'prefix' => '',
            'expire' => 3600,
        ],
        'redis' => [
            'type'     => 'redis',
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'port'     => (int) env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD', ''),
            'select'   => 0,
            'prefix'   => 'ed_cache:',
        ],
    ],
];
