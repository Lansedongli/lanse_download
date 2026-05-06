<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\controller\BaseController;
use think\App;
use think\facade\Db;
use think\Response;

/**
 * 报表数据接口（用户端）
 */
class ReportController extends BaseController
{
    public function summary(): Response
    {
        $total = Db::table('user_recharge')->where('status', 1)
            ->field(['COALESCE(SUM(amount),0) AS total_amount', 'COUNT(*) AS total_orders'])->find();
        $today = date('Y-m-d');
        $ts = Db::table('user_recharge')->where('status', 1)
            ->where('paid_at', '>=', $today.' 00:00:00')->where('paid_at', '<=', $today.' 23:59:59')
            ->field(['COALESCE(SUM(amount),0) AS today_amount', 'COUNT(*) AS today_orders'])->find();
        return $this->success([
            'total_amount' => (float)($total['total_amount']??0),
            'total_orders' => (int)($total['total_orders']??0),
            'today_amount' => (float)($ts['today_amount']??0),
            'today_orders' => (int)($ts['today_orders']??0),
        ]);
    }

    public function trend(): Response
    {
        $days = min(max((int)request()->get('days', 30), 7), 90);
        $start = date('Y-m-d', strtotime("-{$days} days"));
        $rows = Db::table('user_recharge')->where('status', 1)
            ->where('paid_at', '>=', $start.' 00:00:00')
            ->field(["DATE(paid_at) AS date", 'COALESCE(SUM(amount),0) AS amount'])
            ->group('DATE(paid_at)')->order('date','asc')->select()->toArray();
        $map = []; foreach ($rows as $r) $map[$r['date']] = $r;
        $dates = []; $amounts = [];
        for ($i = $days-1; $i >= 0; $i--) {
            $d = date('m/d', strtotime("-{$i} days"));
            $fd = date('Y-m-d', strtotime("-{$i} days"));
            $dates[] = $d;
            $amounts[] = isset($map[$fd]) ? (float)$map[$fd]['amount'] : 0;
        }
        return $this->success(['dates' => $dates, 'amounts' => $amounts]);
    }

    public function channels(): Response
    {
        $rows = Db::table('user_recharge')->where('status', 1)
            ->field(['payment_channel AS name', 'COALESCE(SUM(amount),0) AS value'])
            ->group('payment_channel')->order('value','desc')->select()->toArray();
        $nm = ['alipay'=>'支付宝','wechat'=>'微信支付','card'=>'点卡充值','manual'=>'手动充值'];
        foreach ($rows as &$r) $r['name'] = $nm[$r['name']] ?? $r['name'];
        return $this->success($rows);
    }

    public function packages(): Response
    {
        $raw = Db::table('user_recharge')->where('status', 1)->where('type', 'online')
            ->field(['amount','points','COALESCE(SUM(amount),0) AS total_amount'])
            ->group('amount, points')->order('total_amount','desc')->limit(5)->select()->toArray();
        $pkgs = Db::table('recharge_packages')->field(['amount','points','name'])->select()->toArray();
        $nm = []; foreach ($pkgs as $p) $nm[$p['amount'].'_'.$p['points']] = $p['name'];
        $names = []; $amounts = [];
        foreach ($raw as $r) {
            $k = $r['amount'].'_'.$r['points'];
            $names[] = $nm[$k] ?? ('¥'.$r['amount']);
            $amounts[] = (float)$r['total_amount'];
        }
        return $this->success(['names' => $names, 'amounts' => $amounts]);
    }
}
