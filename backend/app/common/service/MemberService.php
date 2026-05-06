<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\User;
use app\common\model\UserGroup;
use app\common\model\UserPointsLog;
use think\facade\Db;

/**
 * 会员服务
 * 处理会员组切换、点数增减、有效期计算
 */
class MemberService
{
    /**
     * 增加点数（原子操作）
     */
    public function addPoints(int $userId, int $amount, string $type, int $relationId = 0, string $remark = ''): bool
    {
        if ($amount <= 0) return false;

        Db::startTrans();
        try {
            $user = User::lock(true)->find($userId);
            if (!$user) {
                Db::rollback();
                return false;
            }

            $oldBalance = $user->points;
            $user->points += $amount;
            $user->save();

            // 记录日志
            UserPointsLog::create([
                'user_id'     => $userId,
                'type'        => $type,
                'amount'      => $amount,
                'balance'     => $user->points,
                'relation_id' => $relationId,
                'remark'      => $remark,
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    /**
     * 扣除点数（原子操作，防超扣）
     */
    public function deductPoints(int $userId, int $amount, string $type, int $relationId = 0, string $remark = ''): bool
    {
        if ($amount <= 0) return false;

        Db::startTrans();
        try {
            $user = User::lock(true)->find($userId);
            if (!$user || $user->points < $amount) {
                Db::rollback();
                return false;
            }

            $user->points -= $amount;
            $user->save();

            UserPointsLog::create([
                'user_id'     => $userId,
                'type'        => $type,
                'amount'      => -$amount,
                'balance'     => $user->points,
                'relation_id' => $relationId,
                'remark'      => $remark,
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    /**
     * 切换会员组（事务+行锁，防止并发冲突）
     */
    public function changeGroup(int $userId, int $groupId, int $days = 0): bool
    {
        Db::startTrans();
        try {
            $user = User::lock(true)->find($userId);
            if (!$user) {
                Db::rollback();
                return false;
            }

            $group = UserGroup::find($groupId);
            if (!$group || $group->status !== 1) {
                Db::rollback();
                return false;
            }

            $user->group_id = $groupId;
            if ($days > 0) {
                $base = $user->expire_time && strtotime($user->expire_time) > time()
                    ? strtotime($user->expire_time)
                    : time();
                $user->expire_time = date('Y-m-d H:i:s', $base + $days * 86400);
            }
            $user->save();

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    /**
     * 检查每日下载限额
     */
    public function checkDailyLimit(int $userId): bool
    {
        $user = User::find($userId);
        if (!$user) return false;

        $group = UserGroup::find($user->group_id);
        if (!$group) return true;

        // 0表示不限
        if ($group->max_download_day === 0) return true;

        return $user->today_download < $group->max_download_day;
    }

    /**
     * 增加今日下载计数
     */
    public function incTodayDownload(int $userId): void
    {
        User::where('id', $userId)->inc('today_download')->inc('total_download')->update();
    }

    /**
     * 检查并清理过期会员
     */
    public function cleanExpired(): int
    {
        $defaultGroup = UserGroup::where('is_default', 1)->value('id');
        if (!$defaultGroup) return 0;

        return User::where('expire_time', '<', date('Y-m-d H:i:s'))
                   ->where('expire_time', '>', '2000-01-01')
                   ->where('group_id', '<>', $defaultGroup)
                   ->update([
                       'group_id'    => $defaultGroup,
                       'expire_time' => null,
                   ]);
    }
}
