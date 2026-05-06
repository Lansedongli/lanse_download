<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\BaseController;
use think\App;
use think\facade\Db;
use think\Response;

/**
 * 报表统计控制器
 * Phase 5 - 支付报表：充值趋势、渠道占比、套餐排行、汇总数据
 */
class PaymentReportController extends BaseController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /**
     * GET /admin/api/report/summary
     * 汇总卡片：累计金额、累计订单、今日金额、今日订单
     */
    public function summary(): Response
    {
        // 累计（已支付订单）
        $total = Db::table('user_recharge')
            ->where('status', 1)
            ->field([
                'COALESCE(SUM(amount), 0) AS total_amount',
                'COUNT(*) AS total_orders',
            ])
            ->find();

        // 今日
        $today = date('Y-m-d');
        $todayStats = Db::table('user_recharge')
            ->where('status', 1)
            ->where('paid_at', '>=', $today . ' 00:00:00')
            ->where('paid_at', '<=', $today . ' 23:59:59')
            ->field([
                'COALESCE(SUM(amount), 0) AS today_amount',
                'COUNT(*) AS today_orders',
            ])
            ->find();

        return $this->success([
            'total_amount'  => (float) ($total['total_amount'] ?? 0),
            'total_orders'  => (int) ($total['total_orders'] ?? 0),
            'today_amount'  => (float) ($todayStats['today_amount'] ?? 0),
            'today_orders'  => (int) ($todayStats['today_orders'] ?? 0),
        ]);
    }

    /**
     * GET /admin/api/report/trend?days=30
     * 充值趋势：最近N天每日充值金额
     */
    public function trend(): Response
    {
        $days = (int) $this->request->get('days', 30);
        $days = min(max($days, 7), 90); // 限制 7~90 天

        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        $rows = Db::table('user_recharge')
            ->where('status', 1)
            ->where('paid_at', '>=', $startDate . ' 00:00:00')
            ->field([
                "DATE(paid_at) AS date",
                'COALESCE(SUM(amount), 0) AS amount',
                'COUNT(*) AS orders',
            ])
            ->group('DATE(paid_at)')
            ->order('date', 'asc')
            ->select()
            ->toArray();

        // 补全缺失日期（金额为0）
        $dates = [];
        $amounts = [];
        $rowMap = [];
        foreach ($rows as $r) {
            $rowMap[$r['date']] = $r;
        }
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('m/d', strtotime("-{$i} days"));
            $fullDate = date('Y-m-d', strtotime("-{$i} days"));
            $dates[] = $d;
            $amounts[] = isset($rowMap[$fullDate]) ? (float) $rowMap[$fullDate]['amount'] : 0;
        }

        return $this->success([
            'dates'   => $dates,
            'amounts' => $amounts,
        ]);
    }

    /**
     * GET /admin/api/report/channels
     * 渠道占比：各支付渠道的充值总额
     */
    public function channels(): Response
    {
        $rows = Db::table('user_recharge')
            ->where('status', 1)
            ->field([
                'payment_channel AS name',
                'COALESCE(SUM(amount), 0) AS value',
            ])
            ->group('payment_channel')
            ->order('value', 'desc')
            ->select()
            ->toArray();

        // 渠道名称映射
        $nameMap = [
            'alipay' => '支付宝',
            'wechat' => '微信支付',
            'card'   => '点卡充值',
            'manual' => '手动充值',
        ];
        foreach ($rows as &$row) {
            $row['name'] = $nameMap[$row['name']] ?? $row['name'];
        }

        return $this->success($rows);
    }

    /**
     * GET /admin/api/report/packages
     * 套餐销量排行
     */
    public function packages(): Response
    {
        // 直接从 user_recharge 按 amount+points 分组统计，套餐名在 PHP 里映射
        $raw = Db::table('user_recharge')
            ->where('status', 1)
            ->where('type', 'online')
            ->field([
                'amount',
                'points',
                'COUNT(*) AS count',
                'COALESCE(SUM(amount), 0) AS total_amount',
            ])
            ->group('amount, points')
            ->order('total_amount', 'desc')
            ->limit(5)
            ->select()
            ->toArray();

        // 查套餐名映射
        $pkgs = Db::table('recharge_packages')
            ->field(['amount', 'points', 'name'])
            ->select()
            ->toArray();
        $nameMap = [];
        foreach ($pkgs as $p) {
            $key = $p['amount'] . '_' . $p['points'];
            $nameMap[$key] = $p['name'];
        }

        $names = [];
        $amounts = [];
        foreach ($raw as $r) {
            $key = $r['amount'] . '_' . $r['points'];
            $names[] = $nameMap[$key] ?? ('¥' . $r['amount']);
            $amounts[] = (float) $r['total_amount'];
        }

        return $this->success([
            'names'   => $names,
            'amounts' => $amounts,
        ]);
    }
}
