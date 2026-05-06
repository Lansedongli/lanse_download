<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\BaseController;
use app\common\service\StaticGenerateService;
use think\App;

/**
 * 静态化生成 - 管理后台接口
 * 支持同步生成 或 投递到 Redis 队列异步执行
 */
class StaticController extends BaseController
{
    private readonly StaticGenerateService $staticService;

    public function __construct(App $app, StaticGenerateService $staticService)
    {
        parent::__construct($app);
        $this->staticService = $staticService;
    }

    /**
     * 触发生成静态页面
     * POST /admin/api/static/generate
     *
     * @body type  'homepage'|'category'|'detail'|'batch'
     * @body id    int (category/detail 时必填)
     * @body async bool (true=队列异步, false=同步)
     */
    public function generate(): \think\Response
    {
        $data  = $this->request->post();
        $type  = $data['type'] ?? '';
        $id    = (int) ($data['id'] ?? 0);
        $async = !empty($data['async']);

        $allowTypes = ['homepage', 'category', 'detail', 'batch'];
        if (!in_array($type, $allowTypes, true)) {
            return $this->error("无效的生成类型: {$type}，支持: " . implode(', ', $allowTypes), 422);
        }

        if (in_array($type, ['category', 'detail']) && $id <= 0) {
            return $this->error("{$type} 类型必须提供有效的 id 参数", 422);
        }

        // 异步模式：投递到 Redis 队列
        if ($async) {
            try {
                app(\think\Queue::class)->push(\app\common\job\StaticGenerateJob::class, [
                    'type' => $type,
                    'id'   => $id,
                ], 'static');
            } catch (\Throwable $e) {
                return $this->error('队列服务不可用: ' . $e->getMessage(), 500);
            }

            return $this->success([
                'type'  => $type,
                'id'    => $id,
                'async' => true,
            ], '静态化任务已加入队列，后台异步执行');
        }

        // 同步模式
        $result = $this->staticService->generateByType($type, $id);

        if ($result['success']) {
            return $this->success([
                'type'    => $type,
                'id'      => $id,
                'message' => $result['message'] ?? '',
            ], $result['message'] ?? '生成成功');
        }

        return $this->error($result['message'] ?? '生成失败', 500);
    }

    /**
     * 获取当前静态化生成状态
     * GET /admin/api/static/status
     */
    public function status(): \think\Response
    {
        $info = $this->staticService->getStatus();
        return $this->success($info, '获取生成状态成功');
    }
}
