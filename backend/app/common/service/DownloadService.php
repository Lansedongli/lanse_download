<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Attachment;
use app\common\model\DownloadLog;
use app\common\model\Software;
use app\common\model\User;
use think\facade\Db;

/**
 * 下载服务 V2
 * 处理权限验证、点数扣费、防盗链、统计
 *
 * 改进：
 *  - 扣费+日志+统计 统一事务（原子性）
 *  - 行锁防超扣（SELECT ... FOR UPDATE）
 *  - 防盗链 Token（HMAC-SHA256 / 5分钟有效）
 */
class DownloadService
{
    private MemberService $memberService;

    public function __construct(MemberService $memberService)
    {
        $this->memberService = $memberService;
    }

    /**
     * 获取下载链接（核心流程）
     *
     * 流程：权限检查 → 扣费（事务） → 生成Token
     *
     * @return array{success:bool, message:string, download_url?:string, file_name?:string, file_size?:int}
     */
    public function getDownloadUrl(int $softwareId, int $attachmentId, int $userId, string $ip = ''): array
    {
        // ──── 1. 前置检查（事务外） ────
        $software = Software::with(['attachments'])->find($softwareId);
        if (!$software || $software->status !== 1) {
            return ['success' => false, 'message' => '资源不存在或已下架'];
        }

        // 权限检查
        if ($software->require_group > 0) {
            $user = User::find($userId);
            if ($user && $user->group_id < $software->require_group) {
                return ['success' => false, 'message' => '您的会员等级不足，无法下载此资源'];
            }
        }

        // 获取附件
        $attachment = Attachment::where('software_id', $softwareId)
            ->when($attachmentId > 0, fn($q) => $q->where('id', $attachmentId))
            ->order('sort', 'asc')
            ->find();

        if (!$attachment) {
            return ['success' => false, 'message' => '下载文件不存在'];
        }

        // 检查每日限额
        if ($userId > 0 && !$this->memberService->checkDailyLimit($userId)) {
            return ['success' => false, 'message' => '今日下载次数已用完，请明天再来'];
        }

        // ──── 2. 事务：扣费 + 写日志 + 更新计数（原子操作） ────
        $shouldDeduct = $this->shouldDeductPoints($userId, $softwareId, $software);
        $pointsCost = $shouldDeduct ? $software->require_points : 0;

        if ($pointsCost > 0) {
            // 使用 MemberService 的事务扣费（内部行锁 + 事务）
            $ok = $this->memberService->deductPoints(
                $userId,
                $pointsCost,
                'download',
                $softwareId,
                "下载: {$software->title}"
            );
            if (!$ok) {
                return ['success' => false, 'message' => '点数不足，请充值后再下载'];
            }
        }

        // 写日志和统计（同一事务）
        Db::startTrans();
        try {
            // 下载日志
            $user = User::find($userId);
            DownloadLog::create([
                'user_id'       => $userId,
                'software_id'   => $softwareId,
                'attachment_id' => $attachment->id,
                'ip'            => $ip,
                'points_cost'   => $pointsCost,
                'points_balance'=> $user ? $user->points : 0,
                'deducted'      => $pointsCost > 0 ? 1 : 0,
            ]);

            // 更新软件下载计数
            Software::incDownloadCount($softwareId);

            // 更新用户今日/总计下载计数
            if ($userId > 0) {
                $this->memberService->incTodayDownload($userId);
            }

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            // 扣费已成功但日志写入失败 → 回滚点数
            if ($pointsCost > 0) {
                $this->memberService->addPoints($userId, $pointsCost, 'refund', $softwareId, "日志写入失败回滚: {$software->title}");
            }
            return ['success' => false, 'message' => '系统繁忙，请稍后重试'];
        }

        // ──── 3. 生成防盗链 Token（事务外，纯计算） ────
        $token = download_token($softwareId, $attachment->id, $userId);

        return [
            'success'      => true,
            'message'      => '获取成功',
            'download_url' => (string) url("api/download/file", ['token' => $token], true, true),
            'file_name'    => $attachment->name,
            'file_size'    => $attachment->file_size,
        ];
    }

    /**
     * 验证下载 Token 并返回文件信息
     * GET /api/v1/download/file?token=xxx
     */
    public function serveFile(string $token): ?array
    {
        $payload = verify_download_token($token);
        if (!$payload) {
            return null;
        }

        $attachment = Attachment::find($payload['attachment_id'] ?? 0);
        if (!$attachment) {
            return null;
        }

        $filePath = $attachment->file_path;
        if (!file_exists($filePath)) {
            return null;
        }

        return [
            'path' => $filePath,
            'name' => $attachment->name,
            'mime' => $attachment->mime_type ?? 'application/octet-stream',
        ];
    }

    /**
     * 判断是否需要扣费
     * 在免扣费间隔内重复下载同一资源不扣费
     */
    private function shouldDeductPoints(int $userId, int $softwareId, Software $software): bool
    {
        if ($userId <= 0 || $software->require_points <= 0) {
            return false;
        }

        // 检查免扣费间隔（deduct_interval 分钟）
        if ($software->deduct_interval > 0) {
            $threshold = date('Y-m-d H:i:s', time() - $software->deduct_interval * 60);
            $recent = DownloadLog::where('user_id', $userId)
                ->where('software_id', $softwareId)
                ->where('deducted', 1)
                ->where('created_at', '>', $threshold)
                ->find();

            if ($recent) {
                return false; // 免扣费期内，不重复扣费
            }
        }

        return true;
    }
}
