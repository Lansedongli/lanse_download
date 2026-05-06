<?php
declare(strict_types=1);

/**
 * ThinkPHP 8.x PHP内置服务器路由脚本
 * 用于本地开发：php -S 0.0.0.0:8080 -t public router.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$publicDir = __DIR__;

// 如果请求的文件存在，直接返回（静态资源）
if ($uri !== '/' && is_file($publicDir . $uri)) {
    return false;
}

// 设置脚本名为 index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';

// 加载应用入口
require $publicDir . '/index.php';
