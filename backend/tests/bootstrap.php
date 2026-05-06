<?php
declare(strict_types=1);

// ============================================
// PHPUnit Bootstrap — ThinkPHP 8 多应用模式
// ============================================

// 1. 加载 Composer autoload
require __DIR__ . '/../vendor/autoload.php';

// 2. 加载 ThinkPHP 基础文件（不启动 HTTP server，仅初始化容器）
$appPath = __DIR__ . '/../app/';
$thinkPath = __DIR__ . '/../vendor/topthink/framework/src/';

// 模拟 ThinkPHP 基础常量
if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
if (!defined('ROOT_PATH')) define('ROOT_PATH', realpath(__DIR__ . '/../') . DS);

// 3. 初始化 App 实例
$app = new \think\App(ROOT_PATH);

// 4. 绑定核心服务
$app->bind('config', \think\Config::class);
$app->bind('env', \think\Env::class);

// 5. 加载配置（仅加载基础配置，不启动 server）
$app->initialize();

// 6. 设置测试数据库连接
\think\facade\Db::setConfig([
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'type'     => 'mysql',
            'hostname' => env('DB_HOST', '127.0.0.1'),
            'database' => env('DB_NAME', 'empiredown'),
            'username' => env('DB_USER', 'root'),
            'password' => env('DB_PASS', ''),
            'hostport' => '3306',
            'charset'  => 'utf8mb4',
            'prefix'   => '',
        ],
    ],
]);
