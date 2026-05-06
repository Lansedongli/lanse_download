<?php
declare(strict_types=1);

return [
    'default' => 'file',
    'channels' => [
        'file' => [
            'type'      => 'File',
            'path'      => runtime_path('log'),
            'max_files' => 30,
            'level'     => ['error', 'warning', 'info', 'sql'],
        ],
        'operation' => [
            'type'      => 'File',
            'path'      => runtime_path('log/operation'),
            'max_files' => 90,
            'level'     => ['info'],
        ],
    ],
];
