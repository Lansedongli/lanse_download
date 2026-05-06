<?php
declare(strict_types=1);

namespace app\common\controller;

use think\App;
use think\facade\Request;

/**
 * 基础控制器
 */
abstract class BaseController
{
    /** @var App */
    protected $app;

    /** @var Request */
    protected $request;

    /** @var array 当前登录用户信息 */
    protected $currentUser = [];

    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $app->request;
    }

    /**
     * 成功响应
     */
    protected function success(mixed $data = [], string $message = '操作成功', int $code = 200): \think\Response
    {
        return json([
            'code'      => $code,
            'message'   => $message,
            'data'      => $data,
            'timestamp' => time(),
        ]);
    }

    /**
     * 失败响应
     */
    protected function error(string $message = '操作失败', int $code = 500, mixed $data = []): \think\Response
    {
        return json([
            'code'      => $code,
            'message'   => $message,
            'data'      => $data,
            'timestamp' => time(),
        ]);
    }

    /**
     * 获取当前用户ID
     */
    protected function userId(): int
    {
        return $this->currentUser['id'] ?? 0;
    }
}
