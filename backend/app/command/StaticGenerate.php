<?php
declare(strict_types=1);

namespace app\command;

use app\common\service\StaticGenerateService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;

/**
 * 静态页面生成 CLI
 *
 * 用法:
 *   php think static:generate --type=homepage
 *   php think static:generate --type=category --id=1
 *   php think static:generate --type=detail --id=100
 *   php think static:generate --type=batch
 */
class StaticGenerate extends Command
{
    protected function configure(): void
    {
        $this->setName('static:generate')
             ->setDescription('生成静态HTML页面')
             ->addOption('type', 't', Option::VALUE_REQUIRED, '生成类型: homepage/category/detail/batch')
             ->addOption('id', null, Option::VALUE_OPTIONAL, '分类/详情ID');
    }

    protected function execute(Input $input, Output $output): int
    {
        $type = $input->getOption('type') ?: 'batch';
        $id   = (int) $input->getOption('id');

        $output->writeln(">>> 开始生成静态页面: type={$type}" . ($id ? " id={$id}" : ''));

        // 依赖注入 StaticGenerateService
        $service = app(StaticGenerateService::class);

        $result = $service->generateByType($type, $id);

        if ($result['success']) {
            if (isset($result['summary'])) {
                // 批量模式
                foreach ($result['summary'] as $item) {
                    $status = $item['success'] ? '✓' : '✗';
                    $output->writeln("  {$status} [{$item['type']}] {$item['message']}");
                }
            }
            $output->writeln("  {$result['message']}");
            if (!empty($result['file'])) {
                $output->writeln("  文件: {$result['file']}");
            }
        } else {
            $output->writeln("  ✗ {$result['message']}");
        }

        $output->writeln('✓ 静态页面生成完成');
        return $result['success'] ? 0 : 1;
    }
}
