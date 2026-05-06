<?php
declare(strict_types=1);

namespace app\common\middleware;

use think\Request;
use think\Response;

/**
 * CORS 跨域中间件
 * 处理跨域请求，支持前端开发调试和生产环境
 */
class CorsMiddleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        $origin = $request->header('Origin', '*');
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:5173',
            'http://localhost:8080',
        ];

        $allowOrigin = in_array($origin, $allowedOrigins) ? $origin : '*';

        // 预检请求 (OPTIONS)
        if ($request->method(true) === 'OPTIONS') {
            return response('', 204)->header([
                'Access-Control-Allow-Origin'      => $allowOrigin,
                'Access-Control-Allow-Methods'     => 'GET,POST,PUT,DELETE,PATCH,OPTIONS',
                'Access-Control-Allow-Headers'     => 'Content-Type,Authorization,X-Requested-With,Accept,Origin',
                'Access-Control-Max-Age'           => '86400',
                'Access-Control-Allow-Credentials'  => 'true',
            ]);
        }

        /** @var Response $response */
        $response = $next($request);

        // 不能同时使用 * 和 credentials:true
        $headers = [
            'Access-Control-Allow-Origin'      => $allowOrigin === '*' ? '*' : $allowOrigin,
            'Access-Control-Expose-Headers'    => 'Content-Disposition',
            'Access-Control-Allow-Methods'     => 'GET,POST,PUT,DELETE,PATCH,OPTIONS',
            'Access-Control-Allow-Headers'     => 'Content-Type,Authorization,X-Requested-With,Accept,Origin',
        ];
        if ($allowOrigin !== '*') {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }
        return $response->header($headers);
    }
}
