<?php
declare(strict_types=1);

return [
    // URL伪静态后缀
    'url_html_suffix'       => '',

    // 路由中间件
    'middleware' => [
        app\common\middleware\CorsMiddleware::class,
    ],

    // 控制器后缀
    'controller_suffix'     => 'Controller',

    // 控制器命名空间
    'controller_namespace'  => 'app',

    // API 路由分组
    'rule_list' => [],
];
