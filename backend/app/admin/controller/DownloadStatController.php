<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\BaseController;
use think\App;
use think\facade\Db;

/**
 * 下载统计控制器
 * 聚合 download_log 表，按天/按软件维度输出统计数据
 */
class DownloadStatController extends BaseController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /**
     * 下载概览（今日/本周/总计）
     * GET /admin/api/download-stats/summary
     */
    public function summary(): \think\Response
    {
        $today = date('Y-m-d');

        // 今日统计
        $todayStats = Db::table('download_log')
            ->where('created_at', '>=', $today)
            ->field([
                'COUNT(*) as total',
                'SUM(points_cost) as points_total',
                'COUNT(DISTINCT user_id) as unique_users',
                'COUNT(DISTINCT software_id) as unique_softwares',
            ])
            ->find();

        // 本周统计
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekStats = Db::table('download_log')
            ->where('created_at', '>=', $weekStart)
            ->field(['COUNT(*) as total', 'SUM(points_cost) as points_total'])
            ->find();

        // 总计
        $totalStats = Db::table('download_log')
            ->field(['COUNT(*) as total', 'SUM(points_cost) as points_total'])
            ->find();

        return $this->success([
            'today' => $todayStats,
            'week'  => $weekStats,
            'total' => $totalStats,
        ]);
    }

    /**
     * 按天趋势（近30天）
     * GET /admin/api/download-stats/trend?days=30
     */
    public function trend(): \think\Response
    {
        $days = (int) $this->request->get('days', 30);
        $days = min($days, 90);

        $rows = Db::table('download_log')
            ->field([
                'DATE(created_at) as date',
                'COUNT(*) as total',
                'SUM(points_cost) as points_total',
                'COUNT(DISTINCT user_id) as unique_users',
            ])
            ->where('created_at', '>=', date('Y-m-d', strtotime("-{$days} days")))
            ->group('date')
            ->order('date', 'asc')
            ->select();

        return $this->success($rows->toArray());
    }

    /**
     * 按软件排行（TOP N）
     * GET /admin/api/download-stats/top-software?limit=20&days=7
     */
    public function topSoftware(): \think\Response
    {
        $limit = (int) $this->request->get('limit', 20);
        $days  = (int) $this->request->get('days', 7);

        $rows = Db::table('download_log dl')
            ->join('software s', 'dl.software_id = s.id', 'LEFT')
            ->field([
                'dl.software_id',
                's.title as software_name',
                'COUNT(*) as download_count',
                'SUM(dl.points_cost) as points_total',
                'COUNT(DISTINCT dl.user_id) as unique_users',
            ])
            ->when($days > 0, fn($q) => $q->where('dl.created_at', '>=', date('Y-m-d', strtotime("-{$days} days"))))
            ->group('dl.software_id, s.title')
            ->order('download_count', 'desc')
            ->limit($limit)
            ->select();

        return $this->success($rows->toArray());
    }

    /**
     * 按会员组统计下载
     * GET /admin/api/download-stats/by-group
     */
    public function byGroup(): \think\Response
    {
        $rows = Db::table('download_log dl')
            ->join('user u', 'dl.user_id = u.id', 'LEFT')
            ->join('user_group ug', 'u.group_id = ug.id', 'LEFT')
            ->field([
                'COALESCE(ug.name, "游客") as group_name',
                'COUNT(*) as download_count',
                'SUM(dl.points_cost) as points_total',
                'COUNT(DISTINCT dl.user_id) as unique_users',
            ])
            ->group('ug.name')
            ->order('download_count', 'desc')
            ->select();

        return $this->success($rows->toArray());
    }
}
