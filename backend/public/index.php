<?php
declare(strict_types=1);

// +----------------------------------------------------------------------
// | 帝国下载系统 V3.0
// | ThinkPHP 8.1 + PHP 8.1
// +----------------------------------------------------------------------

namespace think;

// 加载基础文件
require __DIR__ . '/../vendor/autoload.php';

// 加载环境变量
if (is_file(__DIR__ . '/../.env')) {
    (new \think\Env())->load(__DIR__ . '/../.env');
}

// 应用初始化
$http = (new App())->http;
$response = $http->run();
$response->send();
$http->end($response);
