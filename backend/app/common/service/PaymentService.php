<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\PointCard;
use app\common\model\PointCardBatch;
use app\common\model\UserRecharge;
use think\facade\Db;

/**
 * 支付充值服务
 * 处理点卡生成、充值、订单管理
 */
class PaymentService
{
    private MemberService $memberService;

    public function __construct(MemberService $memberService)
    {
        $this->memberService = $memberService;
    }

    /**
     * 批量生成点卡
     */
    public function generateCards(int $batchId, int $count, int $points, int $expireDays, int $groupDays = 0): array
    {
        $batch = PointCardBatch::find($batchId);
        if (!$batch) {
            throw new \RuntimeException('批次不存在');
        }

        $cards = [];
        for ($i = 0; $i < $count; $i++) {
            $cardNo  = generate_card_no($batch->batch_no, $batch->used_count + $i + 1);
            $cardPwd = generate_password(16);

            PointCard::create([
                'batch_id'    => $batchId,
                'card_no'     => $cardNo,
                'password'    => $cardPwd,
                'points'      => $points,
                'expire_time' => date('Y-m-d H:i:s', time() + $expireDays * 86400),
                'group_days'  => $groupDays,
                'status'      => 0,
            ]);

            $cards[] = ['card_no' => $cardNo, 'password' => $cardPwd, 'points' => $points];
        }

        // 更新批次计数
        $batch->used_count += $count;
        $batch->save();

        return $cards;
    }

    /**
     * 点卡充值（防双重花费：乐观锁+事务）
     */
    public function rechargeByCard(int $userId, string $cardNo, string $password, string $ip = ''): array
    {
        Db::startTrans();
        try {
            // 在事务内加锁查询，防止并发重复使用
            $card = PointCard::lock(true)->where('card_no', $cardNo)->find();
            if (!$card) {
                Db::rollback();
                return ['success' => false, 'message' => '点卡不存在'];
            }
            if ($card->status !== 0) {
                Db::rollback();
                return ['success' => false, 'message' => match($card->status) { 1 => '点卡已使用', 2 => '点卡已过期', default => '点卡状态异常' }];
            }
            if (strtotime($card->expire_time) < time()) {
                $card->status = 2;
                $card->save();
                Db::rollback();
                return ['success' => false, 'message' => '点卡已过期'];
            }
            if ($card->password !== $password) {
                Db::rollback();
                return ['success' => false, 'message' => '卡密错误'];
            }
            // 标记已使用
            $card->status = 1;
            $card->used_user_id = $userId;
            $card->used_time = date('Y-m-d H:i:s');
            $card->save();

            // 充值点数
            $this->memberService->addPoints($userId, $card->points, 'point_card', $card->id, "点卡充值: {$cardNo}");

            // 赠送会员天数
            if ($card->group_days > 0) {
                $this->memberService->changeGroup($userId, $card->group_id ?: 0, $card->group_days);
            }

            // 充值记录
            $orderNo = generate_order_no('PC');
            UserRecharge::create([
                'user_id'  => $userId,
                'order_no' => $orderNo,
                'type'     => 'point_card',
                'amount'   => 0,
                'points'   => $card->points,
                'group_days' => $card->group_days,
                'status'   => 1,
                'paid_at'  => date('Y-m-d H:i:s'),
                'remark'   => "点卡充值: {$cardNo}",
                'ip'       => $ip,
            ]);

            Db::commit();
            return ['success' => true, 'message' => '充值成功', 'data' => ['points' => $card->points]];
        } catch (\Exception $e) {
            Db::rollback();
            return ['success' => false, 'message' => '充值失败: ' . $e->getMessage()];
        }
    }

    /**
     * 创建在线充值订单
     */
    public function createOrder(int $userId, string $type, float $amount, int $points, mixed $package = null): string
    {
        $orderNo = generate_order_no('ED');
        $data = [
            'user_id'  => $userId,
            'order_no' => $orderNo,
            'type'     => $type,
            'amount'   => $amount,
            'points'   => $points,
            'status'   => 0,
            'ip'       => request()->ip(),
        ];

        if ($package) {
            $data['payment_channel'] = $type;
            $data['remark'] = "在线充值: {$package->name}";
            // 赠送信息
            if ($package->group_days > 0) {
                $data['group_id'] = $package->group_id;
                $data['group_days'] = $package->group_days;
            }
        }

        UserRecharge::create($data);
        return $orderNo;
    }

    /**
     * 根据订单号查询充值记录
     */
    public function getOrderByNo(string $orderNo): ?array
    {
        $order = UserRecharge::where('order_no', $orderNo)->find();
        return $order ? $order->toArray() : null;
    }

    /**
     * 支付回调处理（防重复回调：事务内加锁）
     */
    public function handleCallback(string $orderNo, string $tradeNo, bool $success): bool
    {
        Db::startTrans();
        try {
            $order = UserRecharge::lock(true)->where('order_no', $orderNo)->find();
            if (!$order || $order->status !== 0) {
                Db::rollback();
                return false;
            }

            if ($success) {
                $order->status = 1;
                $order->trade_no = $tradeNo;
                $order->paid_at = date('Y-m-d H:i:s');
                $order->save();

                $this->memberService->addPoints(
                    $order->user_id,
                    $order->points,
                    'recharge',
                    $order->id,
                    "在线充值: {$orderNo}"
                );

                if ($order->group_days > 0) {
                    $this->memberService->changeGroup($order->user_id, $order->group_id, $order->group_days);
                }
            } else {
                $order->status = 2; // 已取消
                $order->save();
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }
}
