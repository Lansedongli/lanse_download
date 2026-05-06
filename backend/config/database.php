<?php
declare(strict_types=1);

return [
    'default'     => env('DB_TYPE', 'mysql'),
    'connections' => [
        'mysql' => [
            'type'            => env('DB_TYPE', 'mysql'),
            'hostname'        => env('DB_HOST', '127.0.0.1'),
            'database'        => env('DB_DATABASE', 'empiredown'),
            'username'        => env('DB_USERNAME', 'root'),
            'password'        => env('DB_PASSWORD', ''),
            'hostport'        => env('DB_PORT', '3306'),
            'charset'         => env('DB_CHARSET', 'utf8mb4'),
            'prefix'          => env('DB_PREFIX', 'ed_'),
            'debug'           => env('APP_DEBUG', false),
            'break_reconnect' => true,
            'params'          => [
                \PDO::ATTR_EMULATE_PREPARES   => false,
                \PDO::ATTR_DEFAULT_FETCH_MODE  => \PDO::FETCH_ASSOC,
                \PDO::MYSQL_ATTR_INIT_COMMAND  => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ],
        ],
    ],
    'migration_namespace' => 'app\\common\\model',
];
