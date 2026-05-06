<?php
declare(strict_types=1);

namespace app\common\middleware;

use think\facade\Cache;
use think\Request;
use think\Response;

/**
 * API 限流中间件
 * 基于 Redis 原子递增，防止接口被恶意刷
 *
 * 使用方式（路由定义）：
 *   Route::get('xxx', 'xxx')->middleware([RateLimitMiddleware::class, 'limit' => '60,1']);
 *   其中 '60,1' 表示 1分钟内最多60次
 */
class RateLimitMiddleware
{
    private int $maxRequests = 60;   // 默认最大请求数
    private int $window = 60;        // 默认时间窗口(秒)

    public function handle(Request $request, \Closure $next, string $limit = ''): Response
    {
        if ($limit) {
            [$this->maxRequests, $this->window] = array_map('intval', explode(',', $limit));
        }

        // 生成限流键(IP + 路由 + 用户)
        $ip = $request->ip();
        $route = $request->baseUrl();
        $userId = $request->user['id'] ?? 0;
        $key = sprintf('rate_limit:%s:%s:%s', md5($route), $ip, $userId);

        // 原子递增（首次调用 Cache::inc 会从0到1，安全无竞态）
        $current = Cache::inc($key);
        if ($current === 1) {
            // 首次设置过期时间（用 set 覆盖值，保留计数）
            Cache::set($key, $current, $this->window);
        }

        if ($current > $this->maxRequests) {
            return $this->tooManyRequests();
        }

        /** @var Response $response */
        $response = $next($request);

        // 添加限流 header
        return $response->header([
            'X-RateLimit-Limit'     => $this->maxRequests,
            'X-RateLimit-Remaining' => max(0, $this->maxRequests - $current),
        ]);
    }

    private function tooManyRequests(): Response
    {
        return json([
            'code'    => 429,
            'message' => '请求过于频繁，请稍后再试',
            'data'    => [],
        ], 429)->header(['Retry-After' => (string) $this->window]);
    }
}
