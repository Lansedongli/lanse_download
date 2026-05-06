<?php
declare(strict_types=1);

namespace app\command;

use app\common\service\DatabaseBackupService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

/**
 * 数据库备份/恢复/优化/修复 CLI 工具
 *
 * 用法:
 *   php think db:backup              全量备份
 *   php think db:backup restore --file=backup_xxx_part1.php  恢复备份
 *   php think db:backup list         备份列表
 *   php think db:backup optimize     优化所有表
 *   php think db:backup repair       修复所有表
 *
 * Cron 示例:
 *   0 3 * * * cd /var/www/html && php think db:backup
 */
class DatabaseBackup extends Command
{
    private DatabaseBackupService $service;

    protected function configure(): void
    {
        $this->setName('db:backup')
             ->addArgument('action', Argument::OPTIONAL, '操作类型: backup|restore|list|optimize|repair', 'backup')
             ->addOption('file', null, Option::VALUE_REQUIRED, '要恢复的备份文件名（restore 操作时必需）')
             ->setDescription('数据库备份/恢复/优化/修复工具');
    }

    protected function execute(Input $input, Output $output): int
    {
        $action = $input->getArgument('action');
        $this->service = new DatabaseBackupService();

        return match ($action) {
            'backup'  => $this->handleBackup($output),
            'restore' => $this->handleRestore($input, $output),
            'list'    => $this->handleList($output),
            'optimize'=> $this->handleOptimize($output),
            'repair'  => $this->handleRepair($output),
            default   => $this->showUsage($output),
        };
    }

    /**
     * 全量备份
     */
    private function handleBackup(Output $output): int
    {
        $output->writeln('<info>开始全量备份...</info>');

        try {
            $result = $this->service->backup('all');
            $output->writeln("<info>✓ 备份成功！共生成 " . count($result['files']) . " 个文件</info>");
            foreach ($result['files'] as $file) {
                $sizeKb = round($file['size'] / 1024, 2);
                $output->writeln("  - {$file['filename']} ({$sizeKb} KB)");
            }
            $output->writeln('<info>备份完成</info>');
            return 0;
        } catch (\Throwable $e) {
            $output->writeln("<error>✗ 备份失败: {$e->getMessage()}</error>");
            return 1;
        }
    }

    /**
     * 恢复备份
     */
    private function handleRestore(Input $input, Output $output): int
    {
        $filename = $input->getOption('file');
        if (empty($filename)) {
            $output->writeln('<error>✗ 请指定要恢复的备份文件: --file=backup_xxx_part1.php</error>');
            return 1;
        }

        $output->writeln("<info>正在恢复备份文件: {$filename}</info>");

        try {
            $result = $this->service->restore($filename);
            if ($result['success']) {
                $output->writeln("<info>✓ {$result['message']}</info>");
                return 0;
            } else {
                $output->writeln("<error>✗ {$result['message']}</error>");
                return 1;
            }
        } catch (\Throwable $e) {
            $output->writeln("<error>✗ 恢复失败: {$e->getMessage()}</error>");
            return 1;
        }
    }

    /**
     * 备份列表
     */
    private function handleList(Output $output): int
    {
        $list = $this->service->getList();

        if (empty($list)) {
            $output->writeln('<comment>暂无备份记录</comment>');
            return 0;
        }

        $output->writeln('<info>备份列表:</info>');
        $output->writeln(str_repeat('-', 80));

        foreach ($list as $batch) {
            $totalKb = round($batch['total_size'] / 1024, 2);
            $output->writeln("<comment>[{$batch['batch']}]</comment> 时间: {$batch['created_at']}  大小: {$totalKb} KB  文件数: " . count($batch['files']));
            foreach ($batch['files'] as $file) {
                $fileKb = round($file['size'] / 1024, 2);
                $output->writeln("  ├─ {$file['filename']} ({$fileKb} KB)");
            }
            $output->writeln('');
        }

        return 0;
    }

    /**
     * 优化所有表
     */
    private function handleOptimize(Output $output): int
    {
        $output->writeln('<info>开始优化所有表...</info>');

        try {
            $result = $this->service->optimize();
            $output->writeln("<info>✓ {$result['message']}</info>");
            foreach ($result['tables'] as $table => $msg) {
                $status = ($msg === 'OK' || $msg === 'Table is already up to date') ? 'info' : 'comment';
                $output->writeln("  <{$status}>- {$table}: {$msg}</{$status}>");
            }
            return 0;
        } catch (\Throwable $e) {
            $output->writeln("<error>✗ 优化失败: {$e->getMessage()}</error>");
            return 1;
        }
    }

    /**
     * 修复所有表
     */
    private function handleRepair(Output $output): int
    {
        $output->writeln('<info>开始修复所有表...</info>');

        try {
            $result = $this->service->repair();
            $output->writeln("<info>✓ {$result['message']}</info>");
            foreach ($result['tables'] as $table => $msg) {
                $status = ($msg === 'OK') ? 'info' : 'comment';
                $output->writeln("  <{$status}>- {$table}: {$msg}</{$status}>");
            }
            return 0;
        } catch (\Throwable $e) {
            $output->writeln("<error>✗ 修复失败: {$e->getMessage()}</error>");
            return 1;
        }
    }

    /**
     * 显示用法说明
     */
    private function showUsage(Output $output): int
    {
        $output->writeln('<error>未知操作: </error>');
        $output->writeln('可用操作:');
        $output->writeln('  php think db:backup              全量备份');
        $output->writeln('  php think db:backup restore --file=xxx.php  恢复备份');
        $output->writeln('  php think db:backup list         备份列表');
        $output->writeln('  php think db:backup optimize     优化所有表');
        $output->writeln('  php think db:backup repair       修复所有表');
        return 1;
    }
}
