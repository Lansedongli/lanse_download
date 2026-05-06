<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\BackupRecord;
use think\facade\Db;

/**
 * 数据库备份/恢复服务
 *
 * 使用 PHP 逐表导出结构+数据，分组成 .php 文件（开头 PHP exit 防止直接访问）
 * 每 N KB 一组（默认 500KB），备份文件存储于 runtime/backup/
 */
class DatabaseBackupService
{
    /** @var string 备份目录 */
    private string $backupDir;

    /** @var string 数据库名 */
    private string $database;

    /** @var string 表前缀 */
    private string $prefix;

    /** @var int 每组最大字节数（默认500KB） */
    private int $maxPartSize;

    /** @var BackupRecord 备份记录模型 */
    private BackupRecord $backupRecord;

    /** @var string 数据库连接名称 */
    private string $connection;

    /**
     * @param int $maxPartSize 每组最大大小（KB），默认500KB
     */
    public function __construct(int $maxPartSize = 500)
    {
        $config = config('database.connections.mysql');
        $this->database   = $config['database'] ?? '';
        $this->prefix     = $config['prefix'] ?? '';
        $this->connection = $config['connection'] ?? 'mysql';
        $this->maxPartSize = $maxPartSize * 1024; // 转为字节

        $this->backupDir = runtime_path('backup');
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }

        $this->backupRecord = new BackupRecord();
    }

    // ========================================
    // 公开方法
    // ========================================

    /**
     * 全量备份
     * 导出所有表的结构+数据，分组写入文件
     *
     * @param string $type 备份类型，默认 'all'
     * @return array 备份文件列表 ['files' => [...], 'total_size' => int]
     */
    public function backup(string $type = 'all'): array
    {
        $tables   = $this->getTables();
        $dateStr  = date('Ymd_His');
        $partNum  = 1;
        $files    = [];
        $buffer   = '';
        $tableList = [];

        foreach ($tables as $table) {
            // 导出表结构
            $structure = $this->exportTableStructure($table);
            // 导出表数据
            $data = $this->exportTableData($table);

            $chunk = $structure . $data;
            $tableList[] = $table;

            // 如果当前表加上去超过大小限制，先写入当前缓冲区
            if ($buffer !== '' && (strlen($buffer) + strlen($chunk)) > $this->maxPartSize) {
                $filename = 'backup_' . $dateStr . '_part' . $partNum . '.php';
                $this->writeBackupFile($filename, $buffer, $partNum, $tableList);
                $files[] = [
                    'filename' => $filename,
                    'size'     => strlen($buffer),
                ];
                $partNum++;
                $buffer    = '';
                $tableList = [];
            }

            $buffer .= $chunk;
        }

        // 写入最后一个分组
        if ($buffer !== '') {
            $filename = 'backup_' . $dateStr . '_part' . $partNum . '.php';
            $this->writeBackupFile($filename, $buffer, $partNum, $tableList);
            $files[] = [
                'filename' => $filename,
                'size'     => strlen($buffer),
            ];
        }

        // 记录备份日志到数据库
        $totalSize = array_sum(array_column($files, 'size'));
        foreach ($files as $file) {
            $this->backupRecord->create([
                'file_name'  => $file['filename'],
                'file_path'  => $file['path'] ?? '',
                'file_size'  => $file['size'],
                'backup_type'=> $type,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return [
            'files'      => $files,
            'total_size' => $totalSize,
        ];
    }

    /**
     * 从备份文件恢复数据库
     *
     * @param string $filename 备份文件名（如 backup_20260505_150000_part1.php）
     * @return array 恢复结果 ['success' => bool, 'message' => string, 'statements' => int]
     */
    public function restore(string $filename): array
    {
        $filepath = $this->backupDir . '/' . $filename;

        if (!file_exists($filepath)) {
            return ['success' => false, 'message' => '备份文件不存在: ' . $filename, 'statements' => 0];
        }

        // 读取文件并移除 PHP 头部
        $content = file_get_contents($filepath);
        if ($content === false) {
            return ['success' => false, 'message' => '无法读取备份文件', 'statements' => 0];
        }

        // 移除 "<?php exit; ?" + ">" 头部及后续换行
        $endTag = '?' . '>';
        $content = preg_replace('/^<\?php\s+exit;\s*\?' . $endTag . '\s*/i', '', $content);
        if (empty(trim($content))) {
            return ['success' => false, 'message' => '备份文件内容为空', 'statements' => 0];
        }

        // 按分号+换行分割 SQL 语句
        $statements = preg_split('/;\s*\n/', $content);
        $execCount  = 0;
        $errors     = [];

        // 禁用外键检查，加快恢复速度
        Db::execute('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($statements as $sql) {
            $sql = trim($sql);
            if ($sql === '' || str_starts_with($sql, '--')) {
                continue;
            }

            try {
                Db::execute($sql);
                $execCount++;
            } catch (\Throwable $e) {
                $errors[] = '执行失败: ' . mb_substr($sql, 0, 80) . '... 错误: ' . $e->getMessage();
            }
        }

        // 恢复外键检查
        Db::execute('SET FOREIGN_KEY_CHECKS = 1');

        $success = count($errors) === 0;
        return [
            'success'    => $success,
            'message'    => $success ? '恢复成功，共执行 ' . $execCount . ' 条 SQL' : '部分恢复（' . $execCount . '/' . ($execCount + count($errors)) . '），错误: ' . implode('; ', $errors),
            'statements' => $execCount,
        ];
    }

    /**
     * 获取备份列表
     *
     * @return array 备份文件列表，按时间倒序
     */
    public function getList(): array
    {
        // 从数据库获取记录
        $records = $this->backupRecord
            ->order('created_at desc')
            ->select()
            ->toArray();

        // 合并同一天同一批次的备份（按文件名前缀分组）
        $groups = [];
        foreach ($records as $record) {
            // 从文件名提取批次标识: backup_20260505_150000
            $basename = preg_replace('/_part\d+\.php$/', '', $record['file_name']);
            if (!isset($groups[$basename])) {
                $groups[$basename] = [
                    'batch'      => $basename,
                    'created_at' => $record['created_at'],
                    'total_size' => 0,
                    'files'      => [],
                ];
            }
            $groups[$basename]['total_size'] += $record['file_size'];
            $groups[$basename]['files'][] = [
                'id'       => $record['id'],
                'filename' => $record['file_name'],
                'size'     => $record['file_size'],
            ];
        }

        return array_values($groups);
    }

    /**
     * 删除备份文件
     *
     * @param string $filename 备份文件名
     * @return array 删除结果
     */
    public function deleteBackup(string $filename): array
    {
        // 从数据库查找记录
        $record = $this->backupRecord->where('file_name', $filename)->find();
        if (!$record) {
            return ['success' => false, 'message' => '备份记录不存在'];
        }

        // 删除物理文件
        $filepath = $this->backupDir . '/' . $filename;
        if (file_exists($filepath)) {
            unlink($filepath);
        }

        // 删除数据库记录
        $record->delete();

        return ['success' => true, 'message' => '备份已删除'];
    }

    /**
     * 优化所有表
     *
     * @return array 优化结果 ['tables' => [...], 'message' => string]
     */
    public function optimize(): array
    {
        $tables  = $this->getTables();
        $results = [];

        foreach ($tables as $table) {
            try {
                $result = Db::query('OPTIMIZE TABLE `' . $table . '`');
                $results[$table] = $result[0]['Msg_text'] ?? 'OK';
            } catch (\Throwable $e) {
                $results[$table] = 'ERROR: ' . $e->getMessage();
            }
        }

        return [
            'tables'  => $results,
            'message' => '优化完成，共处理 ' . count($tables) . ' 张表',
        ];
    }

    /**
     * 修复所有表
     *
     * @return array 修复结果 ['tables' => [...], 'message' => string]
     */
    public function repair(): array
    {
        $tables  = $this->getTables();
        $results = [];

        foreach ($tables as $table) {
            try {
                $result = Db::query('REPAIR TABLE `' . $table . '`');
                $results[$table] = $result[0]['Msg_text'] ?? 'OK';
            } catch (\Throwable $e) {
                $results[$table] = 'ERROR: ' . $e->getMessage();
            }
        }

        return [
            'tables'  => $results,
            'message' => '修复完成，共处理 ' . count($tables) . ' 张表',
        ];
    }

    // ========================================
    // 私有辅助方法
    // ========================================

    /**
     * 获取数据库中所有表名（带前缀）
     */
    private function getTables(): array
    {
        $rows = Db::query('SHOW TABLES FROM `' . $this->database . '`');
        $tables = [];
        $key = 'Tables_in_' . $this->database;

        foreach ($rows as $row) {
            $tableName = $row[$key] ?? '';
            // 只获取带项目前缀的表
            if ($tableName !== '' && str_starts_with($tableName, $this->prefix)) {
                $tables[] = $tableName;
            }
        }

        sort($tables);
        return $tables;
    }

    /**
     * 导出表结构（CREATE TABLE 语句）
     */
    private function exportTableStructure(string $table): string
    {
        $row = Db::query('SHOW CREATE TABLE `' . $table . '`');
        if (empty($row)) {
            return '';
        }

        $createSql = $row[0]['Create Table'] ?? '';
        if ($createSql === '') {
            return '';
        }

        $output = "\n-- ----------------------------\n";
        $output .= '-- Table structure for ' . $table . "\n";
        $output .= "-- ----------------------------\n";
        $output .= 'DROP TABLE IF EXISTS `' . $table . "`;\n";
        $output .= $createSql . ";\n";

        return $output;
    }

    /**
     * 导出表数据（INSERT 语句）
     * 采用分批读取，避免大表撑爆内存
     */
    private function exportTableData(string $table): string
    {
        // 获取总行数
        $countRow = Db::query('SELECT COUNT(*) AS cnt FROM `' . $table . '`');
        $totalRows = (int) ($countRow[0]['cnt'] ?? 0);

        if ($totalRows === 0) {
            return '';
        }

        $output = "\n-- ----------------------------\n";
        $output .= '-- Records of ' . $table . ' (' . $totalRows . " rows)\n";
        $output .= "-- ----------------------------\n";

        // 分批读取，每批 500 行
        $batchSize = 500;
        $offset    = 0;

        while ($offset < $totalRows) {
            $rows = Db::query('SELECT * FROM `' . $table . '` LIMIT ' . $offset . ', ' . $batchSize);

            foreach ($rows as $row) {
                $values = [];
                foreach ($row as $val) {
                    if ($val === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = "'" . addslashes((string) $val) . "'";
                    }
                }
                $output .= 'INSERT INTO `' . $table . '` VALUES (' . implode(', ', $values) . ");\n";
            }

            $offset += $batchSize;
        }

        return $output;
    }

    /**
     * 写入备份文件（带 PHP 头部保护）
     */
    private function writeBackupFile(string $filename, string $content, int $partNum, array $tables): void
    {
        $header = '<' . '?php exit; ?' . '>' . "\n";
        $header .= "-- ============================================\n";
        $header .= '-- Database Backup: ' . $this->database . "\n";
        $header .= '-- Date: ' . date('Y-m-d H:i:s') . "\n";
        $header .= '-- Part: ' . $partNum . "\n";
        $header .= '-- Tables: ' . implode(', ', $tables) . "\n";
        $header .= "-- Generator: edown DatabaseBackupService\n";
        $header .= "-- ============================================\n\n";

        $filepath = $this->backupDir . '/' . $filename;
        file_put_contents($filepath, $header . $content, LOCK_EX);
    }
}
