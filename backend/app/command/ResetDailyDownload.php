<?php
declare(strict_types=1);

namespace app\command;

use app\common\model\User;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 重置每日下载计数
 * CLI: php think download:reset-daily
 * Cron: 0 0 * * * cd /var/www/html && php think download:reset-daily
 */
class ResetDailyDownload extends Command
{
    protected function configure(): void
    {
        $this->setName('download:reset-daily')
             ->setDescription('每日0点重置所有用户的今日下载计数');
    }

    protected function execute(Input $input, Output $output): int
    {
        $count = User::where('today_download', '>', 0)
                     ->update(['today_download' => 0]);

        $output->writeln("✓ 重置完成，共处理 {$count} 个用户");
        return 0;
    }
}
