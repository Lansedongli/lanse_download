<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\BaseController;
use think\App;
use think\facade\Db;
use think\Response;

/**
 * 日志管理控制器
 * Phase 5 - 操作日志 & 登录日志查看
 */
class LogController extends BaseController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /**
     * GET /admin/api/logs/operation?page=1&page_size=20&module=&admin_id=
     * 操作日志列表（支持筛选）
     */
    public function operation(): Response
    {
        $page = max(1, (int) $this->request->get('page', 1));
        $pageSize = min(max(10, (int) $this->request->get('page_size', 20)), 100);
        $module = $this->request->get('module', '');
        $adminId = $this->request->get('admin_id', '');

        $query = Db::table('operation_log')->order('id', 'desc');

        if ($module) {
            $query->where('module', $module);
        }
        if ($adminId !== '') {
            $query->where('admin_id', (int) $adminId);
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        return $this->success([
            'total'     => $total,
            'page'      => $page,
            'page_size' => $pageSize,
            'list'      => $list,
        ]);
    }

    /**
     * GET /admin/api/logs/login?page=1&page_size=20&username=&status=
     * 登录日志列表
     */
    public function login(): Response
    {
        $page = max(1, (int) $this->request->get('page', 1));
        $pageSize = min(max(10, (int) $this->request->get('page_size', 20)), 100);
        $username = $this->request->get('username', '');
        $status = $this->request->get('status', '');

        $query = Db::table('user_login_log')->order('id', 'desc');

        if ($username) {
            $query->where('username', 'like', "%{$username}%");
        }
        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        return $this->success([
            'total'     => $total,
            'page'      => $page,
            'page_size' => $pageSize,
            'list'      => $list,
        ]);
    }

    /**
     * GET /admin/api/logs/modules
     * 操作日志模块列表（用于筛选下拉）
     */
    public function modules(): Response
    {
        $modules = Db::table('operation_log')
            ->field('module')
            ->group('module')
            ->order('module')
            ->select()
            ->column('module');

        return $this->success($modules);
    }

    /**
     * GET /admin/api/logs/stats
     * 日志统计概览
     */
    public function stats(): Response
    {
        $today = date('Y-m-d');

        // 今日操作日志数
        $todayOps = Db::table('operation_log')
            ->where('created_at', '>=', $today . ' 00:00:00')
            ->count();

        // 今日登录次数
        $todayLogins = Db::table('user_login_log')
            ->where('created_at', '>=', $today . ' 00:00:00')
            ->count();

        // 总操作日志
        $totalOps = Db::table('operation_log')->count();

        return $this->success([
            'today_operations' => $todayOps,
            'today_logins'     => $todayLogins,
            'total_operations' => $totalOps,
        ]);
    }
}
