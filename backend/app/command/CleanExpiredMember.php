<?php
declare(strict_types=1);

namespace app\command;

use app\common\service\MemberService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 清理过期会员
 * CLI: php think member:clean-expired
 * Cron: 0 1 * * * cd /var/www/html && php think member:clean-expired
 */
class CleanExpiredMember extends Command
{
    protected function configure(): void
    {
        $this->setName('member:clean-expired')
             ->setDescription('清理过期会员，自动降级为默认会员组');
    }

    protected function execute(Input $input, Output $output): int
    {
        $memberService = app(MemberService::class);
        $count = $memberService->cleanExpired();

        $output->writeln("✓ 清理完成，共处理 {$count} 个过期会员");
        return 0;
    }
}
