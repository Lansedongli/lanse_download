<?php
declare(strict_types=1);

return [
    'secret'  => env('JWT_SECRET', ''),
    'ttl'     => (int) env('JWT_TTL', 7200),    // 令牌有效期(秒)
    'refresh_ttl' => 1209600,                     // 刷新令牌有效期(14天)
    'algo'    => 'HS256',
    'issuer'  => 'empiredown',
    'audience' => 'empiredown-api',
];
