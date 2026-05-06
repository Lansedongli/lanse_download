<?php
declare(strict_types=1);

namespace app\common\middleware;

use app\common\service\JwtService;
use think\facade\Db;
use think\Request;
use think\Response;

/**
 * 管理员认证 + 权限中间件
 * 后台接口专用，验证管理权限 + RBAC 权限校验
 */
class AdminAuthMiddleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        $header = $request->header('Authorization', '');
        if (empty($header) || !str_starts_with($header, 'Bearer ')) {
            return $this->unauthorized('请先登录后台');
        }

        $token = substr($header, 7);

        $jwtService = app(JwtService::class);
        $payload = $jwtService->verifyToken($token);

        if (!$payload || ($payload['guard'] ?? '') !== 'admin') {
            return $this->unauthorized('无管理权限');
        }

        $request->admin = [
            'id'       => $payload['user_id'] ?? 0,
            'username' => $payload['username'] ?? '',
            'role_id'  => $payload['role_id'] ?? 0,
        ];

        // ========== RBAC 权限校验 ==========
        if (!$this->checkPermission($request)) {
            return json([
                'code'    => 403,
                'message' => '没有操作权限',
                'data'    => [],
            ], 403);
        }

        return $next($request);
    }

    /**
     * 权限检查
     * 1. 超级管理员 (role_id=1 或 permissions=["*"]) 放行
     * 2. 解析当前请求对应的权限标识 (module:action)
     * 3. 检查用户角色的 permissions 中是否包含该标识
     */
    private function checkPermission(Request $request): bool
    {
        $roleId = $request->admin['role_id'] ?? 0;
        if (!$roleId) {
            return false;
        }

        // 查角色权限
        $role = Db::table('role')->where('id', $roleId)->where('status', 1)->find();
        if (!$role) {
            return false;
        }

        $permissions = json_decode($role['permissions'] ?? '[]', true) ?: [];

        // 超级管理员：拥有 "*" 权限，全部放行
        if (in_array('*', $permissions, true)) {
            return true;
        }

        // 解析当前请求需要的权限标识
        $requiredPermission = $this->urlToPermission($request);
        if (!$requiredPermission) {
            // 无法识别的 URL 默认放行（兼容旧路由）
            return true;
        }

        // 检查是否有该权限
        return in_array($requiredPermission, $permissions, true);
    }

    /**
     * URL到权限标识映射
     * 格式: module:action
     *
     * 规则: /admin/api/{module}/{action} → module:action
     *    或 /admin/api/{module}/<id>    → module:{HTTP方法映射}
     */
    private function urlToPermission(Request $request): ?string
    {
        $path = $request->pathinfo();
        $method = $request->method();

        // 去掉前缀 admin/api/
        $path = preg_replace('#^admin/api/#', '', $path);

        // 特殊路径映射
        $specialMap = [
            'auth/me'        => null,        // 个人资料无需权限
            'auth/logout'    => null,        // 退出无需权限
        ];

        // 路由到权限的完整映射表
        $routeMap = [
            // 角色管理
            'roles'              => 'role',
            // 管理员管理
            'admin-users'        => 'admin_user',
            // 用户管理
            'users'              => 'user',
            // 软件管理
            'software'           => 'software',
            // 附件管理（归软件模块）
            'attachments'        => 'software',
            // 分类管理
            'categories'         => 'category',
            // 支付渠道/套餐
            'pay/channels'       => 'pay',
            'pay/packages'       => 'pay',
            // 充值
            'recharges'          => 'recharge',
            // 报表 + 下载统计
            'report'             => 'report',
            'download-stats'     => 'report',
            // 数据库
            'db'                 => 'db',
            // 万能接口
            'integration'        => 'integration',
            // 广告管理
            'ads'                => 'ad',
            // 静态化
            'static'             => 'static',
            // 点卡
            'point-cards'        => 'recharge',
            // 模板变量
            'template-vars'      => 'system',
            // 系统配置
            'system-configs'     => 'system',
        ];

        // HTTP 方法到动作映射
        $methodActions = [
            'GET'    => 'list',
            'POST'   => 'create',
            'PUT'    => 'update',
            'DELETE' => 'delete',
        ];

        $action = $methodActions[$method] ?? 'list';

        // 先检查特殊映射
        foreach ($specialMap as $pattern => $perm) {
            if (str_starts_with($path, $pattern)) {
                return $perm;
            }
        }

        // 遍历路由映射
        foreach ($routeMap as $routePrefix => $module) {
            if (str_starts_with($path, $routePrefix)) {
                return "{$module}:{$action}";
            }
        }

        // 未知路径，默认放行
        return null;
    }

    private function unauthorized(string $message): Response
    {
        return json([
            'code'    => 401,
            'message' => $message,
            'data'    => [],
        ], 401);
    }
}
