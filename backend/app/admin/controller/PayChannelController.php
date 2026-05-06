<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\BaseController;
use app\common\model\PayChannel;
use think\App;
use think\facade\Validate;

/**
 * 支付渠道配置管理（管理员端）
 */
class PayChannelController extends BaseController
{
    private readonly PayChannel $model;

    public function __construct(App $app, PayChannel $model)
    {
        parent::__construct($app);
        $this->model = $model;
    }

    /**
     * 渠道列表
     * GET /admin/api/pay/channels
     */
    public function index(): \think\Response
    {
        $list = $this->model->order('sort_order', 'asc')->select()->toArray();
        return $this->success($list);
    }

    /**
     * 创建/更新渠道
     * POST /admin/api/pay/channels
     */
    public function create(): \think\Response
    {
        $data = $this->request->post();

        $validate = Validate::rule([
            'name'        => 'require|max:50',
            'code'        => 'require|alpha|max:30',
            'app_id'      => 'max:100',
            'api_key'     => 'max:500',
            'notify_url'  => 'max:500',
        ])->message([
            'name.require' => '渠道名称不能为空',
            'code.require' => '渠道代码不能为空',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        $channel = $this->model->create($data);
        return $this->success($channel->toArray(), '渠道创建成功');
    }

    /**
     * 更新渠道
     * PUT /admin/api/pay/channels/:id
     */
    public function update(int $id): \think\Response
    {
        $channel = $this->model->find($id);
        if (!$channel) {
            return $this->error('渠道不存在', 404);
        }

        $data = $this->request->put();
        $channel->save($data);
        return $this->success($channel->toArray(), '渠道更新成功');
    }

    /**
     * 删除渠道
     * DELETE /admin/api/pay/channels/:id
     */
    public function delete(int $id): \think\Response
    {
        $channel = $this->model->find($id);
        if (!$channel) {
            return $this->error('渠道不存在', 404);
        }
        $channel->delete();
        return $this->success([], '渠道已删除');
    }
}
