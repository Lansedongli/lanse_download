<?php
declare(strict_types=1);

/**
 * 控制台命令注册
 * ThinkPHP 8 自定义命令配置
 */
return [
    'commands' => [
        // 数据库备份/恢复/优化/修复
        \app\command\DatabaseBackup::class,

        // 静态化页面生成
        \app\command\StaticGenerate::class,

        // 定时任务：每日下载次数重置
        \app\command\ResetDailyDownload::class,

        // 定时任务：清理过期会员
        \app\command\CleanExpiredMember::class,
    ],
];
