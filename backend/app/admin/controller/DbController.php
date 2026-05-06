<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\BaseController;
use app\common\model\BackupRecord;
use app\common\service\DatabaseBackupService;
use think\App;

/**
 * 数据库备份管理控制器（管理员端）
 *
 * API 路由前缀: /admin/api/db/*
 * 受 AdminAuthMiddleware 保护
 */
class DbController extends BaseController
{
    private DatabaseBackupService $backupService;

    public function __construct(App $app, DatabaseBackupService $backupService)
    {
        parent::__construct($app);
        $this->backupService = $backupService;
    }

    /**
     * 全量备份
     * POST /admin/api/db/backup
     */
    public function backup(): \think\Response
    {
        try {
            $result = $this->backupService->backup('all');
            return $this->success($result, '备份成功');
        } catch (\Throwable $e) {
            return $this->error('备份失败: ' . $e->getMessage());
        }
    }

    /**
     * 备份列表
     * GET /admin/api/db/list
     */
    public function list(): \think\Response
    {
        try {
            $list = $this->backupService->getList();
            return $this->success($list, '获取成功');
        } catch (\Throwable $e) {
            return $this->error('获取备份列表失败: ' . $e->getMessage());
        }
    }

    /**
     * 恢复备份
     * POST /admin/api/db/restore
     * Body: { "file": "backup_xxx_part1.php" }
     */
    public function restore(): \think\Response
    {
        $data = $this->request->post();

        if (empty($data['file'])) {
            return $this->error('请指定要恢复的备份文件', 422);
        }

        try {
            $result = $this->backupService->restore($data['file']);
            if ($result['success']) {
                return $this->success($result, $result['message']);
            }
            return $this->error($result['message']);
        } catch (\Throwable $e) {
            return $this->error('恢复失败: ' . $e->getMessage());
        }
    }

    /**
     * 删除备份
     * DELETE /admin/api/db/backup/<id>
     * @param int $id 备份记录ID
     */
    public function delete(int $id): \think\Response
    {
        try {
            // 根据ID查找备份文件名
            $record = (new BackupRecord())->find($id);
            if (!$record) {
                return $this->error('备份记录不存在', 404);
            }

            $result = $this->backupService->deleteBackup($record->filename);
            if ($result['success']) {
                return $this->success([], $result['message']);
            }
            return $this->error($result['message'], 404);
        } catch (\Throwable $e) {
            return $this->error('删除失败: ' . $e->getMessage());
        }
    }

    /**
     * 优化所有表
     * POST /admin/api/db/optimize
     */
    public function optimize(): \think\Response
    {
        try {
            $result = $this->backupService->optimize();
            return $this->success($result, $result['message']);
        } catch (\Throwable $e) {
            return $this->error('优化失败: ' . $e->getMessage());
        }
    }

    /**
     * 修复所有表
     * POST /admin/api/db/repair
     */
    public function repair(): \think\Response
    {
        try {
            $result = $this->backupService->repair();
            return $this->success($result, $result['message']);
        } catch (\Throwable $e) {
            return $this->error('修复失败: ' . $e->getMessage());
        }
    }
}
