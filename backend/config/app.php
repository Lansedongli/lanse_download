<?php
declare(strict_types=1);

return [
    // 应用名称
    'app_name'           => env('APP_NAME', '帝国下载系统'),

    // 应用地址
    'app_host'           => env('APP_HOST', ''),

    // 调试模式
    'app_debug'          => env('APP_DEBUG', false),

    // 默认时区
    'default_timezone'   => 'Asia/Shanghai',

    // 默认语言
    'default_lang'       => 'zh-cn',

    // 开启多应用模式
    'auto_multi_app'     => true,

    // 错误级别
    'error_level'        => E_ALL,

    // 异常处理类
    'exception_handle'   => \app\common\exception\ExceptionHandle::class,
];
