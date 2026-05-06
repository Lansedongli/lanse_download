<?php
declare(strict_types=1);

return [
    'default' => 'redis',
    'connections' => [
        'redis' => [
            'type'       => 'redis',
            'queue'      => 'ed_queue',
            'host'       => env('REDIS_HOST', '127.0.0.1'),
            'port'       => env('REDIS_PORT', 6379),
            'password'   => env('REDIS_PASSWORD', ''),
            'select'     => 0,
            'timeout'    => 0,
            'persistent' => false,
        ],
    ],
    'failed' => [
        'type'  => 'none',
        'table' => 'failed_jobs',
    ],
];
