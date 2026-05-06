<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\controller\BaseController;
use app\common\model\UserRecharge as UserRechargeModel;
use app\common\service\PaymentService;
use think\App;
use think\facade\Validate;

/**
 * 前台充值控制器
 * 处理点卡充值和充值记录查询
 * 需认证访问 (AuthMiddleware)
 */
class RechargeController extends BaseController
{
    private readonly PaymentService $paymentService;
    private readonly UserRechargeModel $userRechargeModel;

    public function __construct(App $app, PaymentService $paymentService, UserRechargeModel $userRechargeModel)
    {
        parent::__construct($app);
        $this->paymentService     = $paymentService;
        $this->userRechargeModel  = $userRechargeModel;

        // 注入 AuthMiddleware 设置的用户信息
        if (isset($this->request->user)) {
            $this->currentUser = $this->request->user;
        }
    }

    /**
     * 点卡充值（需登录）
     * POST /api/v1/recharge/card
     *
     * @body card_no  string 卡号
     * @body password string 卡密
     */
    public function cardRecharge(): \think\Response
    {
        $data = $this->request->post();

        $validate = Validate::rule([
            'card_no'  => 'require|alphaNum',
            'password' => 'require|alphaNum',
        ])->message([
            'card_no.require'  => '卡号不能为空',
            'card_no.alphaNum'  => '卡号格式不正确',
            'password.require' => '卡密不能为空',
            'password.alphaNum' => '卡密格式不正确',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        $result = $this->paymentService->rechargeByCard(
            $this->userId(),
            $data['card_no'],
            $data['password'],
            $this->request->ip()
        );

        if (!$result['success']) {
            return $this->error($result['message'], 403);
        }

        return $this->success($result['data'] ?? [], '充值成功');
    }

    /**
     * 充值记录查询
     * GET /api/v1/recharge/records?page=1&limit=20&status=
     */
    public function records(): \think\Response
    {
        $page   = max(1, (int) $this->request->get('page', 1));
        $limit  = min(100, max(1, (int) $this->request->get('limit', 20)));
        $status = $this->request->get('status');

        $query = $this->userRechargeModel
            ->where('user_id', $this->userId())
            ->order('id desc');

        // 状态筛选（0待支付,1已支付,2已取消）
        if ($status !== null && $status !== '') {
            $statusInt = (int) $status;
            if (in_array($statusInt, [0, 1, 2], true)) {
                $query->where('status', $statusInt);
            }
        }

        $result = $query->paginate(['page' => $page, 'list_rows' => $limit]);

        return $this->success([
            'total' => $result->total(),
            'page'  => $page,
            'limit' => $limit,
            'list'  => $result->items(),
        ]);
    }
}
