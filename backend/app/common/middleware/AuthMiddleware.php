<?php
declare(strict_types=1);

namespace app\common\middleware;

use app\common\service\JwtService;
use think\Request;
use think\Response;

/**
 * JWT 认证中间件
 * 验证请求的 Bearer Token，将用户信息注入请求上下文
 */
class AuthMiddleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        // 获取 Authorization header
        $header = $request->header('Authorization', '');
        if (empty($header) || !str_starts_with($header, 'Bearer ')) {
            return $this->unauthorized('请先登录');
        }

        $token = substr($header, 7);

        // 验证 Token
        $jwtService = app(JwtService::class);
        $payload = $jwtService->verifyToken($token);

        if (!$payload) {
            return $this->unauthorized('登录已过期，请重新登录');
        }

        // 将用户信息注入到请求属性中
        $request->user = [
            'id'       => $payload['user_id'] ?? 0,
            'username' => $payload['username'] ?? '',
            'group_id'  => $payload['group_id'] ?? 0,
            'level'     => $payload['level'] ?? 1,
            'guard'     => $payload['guard'] ?? 'api',
        ];

        return $next($request);
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
