<?php
declare(strict_types=1);

namespace app\common\exception;

use think\exception\Handle;
use think\exception\HttpException;
use think\exception\ValidateException;
use think\Response;

/**
 * 全局异常处理
 * 统一返回 JSON 格式的错误信息
 */
class ExceptionHandle extends Handle
{
    public function render(\Exception $e): Response
    {
        // 参数验证异常
        if ($e instanceof ValidateException) {
            return json([
                'code'    => 422,
                'message' => $e->getMessage(),
                'data'    => [],
            ], 422);
        }

        // HTTP 异常 (404/405等)
        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
            return json([
                'code'    => $statusCode,
                'message' => $e->getMessage() ?: '请求的资源不存在',
                'data'    => [],
            ], $statusCode);
        }

        // 调试模式返回详细错误
        if (app()->isDebug()) {
            return parent::render($e);
        }

        // 生产环境返回通用错误
        return json([
            'code'    => 500,
            'message' => '服务器内部错误',
            'data'    => [],
        ], 500);
    }
}
