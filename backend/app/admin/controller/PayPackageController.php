<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\BaseController;
use app\common\model\RechargePackage;
use think\App;
use think\facade\Validate;

/**
 * 充值套餐管理（管理员端）
 */
class PayPackageController extends BaseController
{
    private readonly RechargePackage $model;

    public function __construct(App $app, RechargePackage $model)
    {
        parent::__construct($app);
        $this->model = $model;
    }

    /**
     * 套餐列表
     * GET /admin/api/pay/packages
     */
    public function index(): \think\Response
    {
        $list = $this->model->order('sort_order', 'asc')->select()->toArray();
        return $this->success($list);
    }

    /**
     * 创建套餐
     * POST /admin/api/pay/packages
     */
    public function create(): \think\Response
    {
        $data = $this->request->post();

        $validate = Validate::rule([
            'name'   => 'require|max:100',
            'amount' => 'require|float|gt:0',
            'points' => 'require|integer|gt:0',
        ])->message([
            'name.require'   => '套餐名称不能为空',
            'amount.require' => '支付金额不能为空',
            'amount.gt'      => '金额必须大于0',
            'points.require' => '点数不能为空',
            'points.gt'      => '点数必须大于0',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        $package = $this->model->create($data);
        return $this->success($package->toArray(), '套餐创建成功');
    }

    /**
     * 更新套餐
     * PUT /admin/api/pay/packages/:id
     */
    public function update(int $id): \think\Response
    {
        $package = $this->model->find($id);
        if (!$package) {
            return $this->error('套餐不存在', 404);
        }

        $data = $this->request->put();
        $package->save($data);
        return $this->success($package->toArray(), '套餐更新成功');
    }

    /**
     * 删除套餐
     * DELETE /admin/api/pay/packages/:id
     */
    public function delete(int $id): \think\Response
    {
        $package = $this->model->find($id);
        if (!$package) {
            return $this->error('套餐不存在', 404);
        }
        $package->delete();
        return $this->success([], '套餐已删除');
    }
}
