<?php
declare(strict_types=1);

namespace app\common\middleware;

use think\facade\Db;
use think\Request;
use think\Response;

/**
 * 操作日志中间件
 * 自动记录管理后台的操作（AOP拦截），异步写入
 *
 * 使用方式：
 *   Route::group('', fn() => ...)->middleware([LogMiddleware::class]);
 */
class LogMiddleware
{
    /**
     * 需要记录的 HTTP 方法（写操作）
     */
    private const LOG_METHODS = ['POST', 'PUT', 'DELETE', 'PATCH'];

    /**
     * 跳过记录的路由（不记录敏感信息的接口）
     */
    private const SKIP_ROUTES = [
        'auth/login',
        'auth/logout',
    ];

    public function handle(Request $request, \Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // 仅记录写操作
        $method = strtoupper($request->method());
        if (!in_array($method, self::LOG_METHODS, true)) {
            return $response;
        }

        // 跳过白名单路由
        $path = trim($request->pathinfo(), '/');
        foreach (self::SKIP_ROUTES as $skip) {
            if (str_contains($path, $skip)) {
                return $response;
            }
        }

        // 提取管理员信息
        $admin = $request->admin ?? [];
        $adminId = $admin['id'] ?? 0;
        $username = $admin['username'] ?? 'system';

        // 提取操作信息
        $module = $this->extractModule($path);
        $action = $this->extractAction($path, $method);

        // 提取目标ID（从URL参数或路径中提取数字ID）
        $targetId = $this->extractTargetId($request);

        // 记录操作内容摘要
        $content = $this->buildContent($method, $path, $request);

        // 异步写入（利用ThinkPHP的Db方法，轻量级）
        try {
            Db::table('operation_log')->insert([
                'admin_id'   => $adminId,
                'username'   => $username,
                'module'     => $module,
                'action'     => $action,
                'target_id'  => $targetId,
                'content'    => $content,
                'ip'         => $request->ip(),
                'user_agent' => mb_substr($request->header('User-Agent', ''), 0, 500),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // 日志记录失败不影响主流程
        }

        return $response;
    }

    /**
     * 从路径中提取模块名
     */
    private function extractModule(string $path): string
    {
        // /admin/api/pay/channels → pay
        $parts = explode('/', trim($path, '/'));
        // 跳过 admin/api 前缀
        foreach ($parts as $i => $part) {
            if ($part === 'admin' || $part === 'api') {
                continue;
            }
            // 第一个非admin/api的段就是模块
            return $part;
        }
        return 'unknown';
    }

    /**
     * 提取操作名
     */
    private function extractAction(string $path, string $method): string
    {
        $methodMap = [
            'POST'   => 'create',
            'PUT'    => 'update',
            'DELETE' => 'delete',
            'PATCH'  => 'patch',
        ];
        $action = $methodMap[$method] ?? strtolower($method);
        // /admin/api/pay/channels/123 → pay.channels.update
        $parts = explode('/', trim($path, '/'));
        $last = end($parts);
        // 如果最后一段是数字ID，取倒数第二段作为资源名
        if (is_numeric($last) && count($parts) > 1) {
            $last = $parts[count($parts) - 2];
        }
        return $last . '.' . $action;
    }

    /**
     * 从请求中提取目标ID
     */
    private function extractTargetId(Request $request): int
    {
        // 尝试从路由参数中提取id
        $id = $request->param('id', 0);
        if ($id) {
            return (int) $id;
        }
        // 尝试从路径中提取最后的数字
        $path = trim($request->pathinfo(), '/');
        if (preg_match('/(\d+)$/', $path, $m)) {
            return (int) $m[1];
        }
        return 0;
    }

    /**
     * 构建操作内容摘要
     */
    private function buildContent(string $method, string $path, Request $request): string
    {
        $summary = strtoupper($method) . ' ' . $path;
        // 对于POST/PUT，记录请求参数摘要（脱敏处理）
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $params = $request->param();
            // 过滤敏感字段
            $safeParams = array_diff_key($params, array_flip(['password', 'private_key', 'api_key', 'secret']));
            if (!empty($safeParams)) {
                $json = json_encode($safeParams, JSON_UNESCAPED_UNICODE);
                if ($json && strlen($json) > 500) {
                    $json = mb_substr($json, 0, 497) . '...';
                }
                $summary .= ' | ' . $json;
            }
        }
        return $summary;
    }
}
