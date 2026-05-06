<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\controller\BaseController;
use app\common\gateway\PayGatewayFactory;
use app\common\model\RechargePackage;
use app\common\service\PaymentService;
use think\App;
use think\facade\Log;
use think\facade\Validate;

/**
 * 在线支付控制器（前台）
 */
class PaymentController extends BaseController
{
    private readonly PaymentService $paymentService;

    public function __construct(App $app, PaymentService $paymentService)
    {
        parent::__construct($app);
        $this->paymentService = $paymentService;

        if (isset($this->request->user)) {
            $this->currentUser = $this->request->user;
        }
    }

    /**
     * 获取充值套餐列表（公开）
     * GET /api/v1/payment/packages
     */
    public function packages(): \think\Response
    {
        $packages = RechargePackage::where('status', 1)
            ->order('sort_order', 'asc')
            ->select()
            ->toArray();

        return $this->success($packages);
    }

    /**
     * 获取可用支付渠道（公开）
     * GET /api/v1/payment/channels
     */
    public function channels(): \think\Response
    {
        return $this->success(PayGatewayFactory::enabledChannels());
    }

    /**
     * 创建支付订单并获取支付参数（需登录）
     * POST /api/v1/payment/create
     * @body package_id int 套餐ID
     * @body channel    string 支付渠道(alipay/wechat)
     */
    public function create(): \think\Response
    {
        $data = $this->request->post();

        $validate = Validate::rule([
            'package_id' => 'require|integer|gt:0',
            'channel'    => 'require|in:alipay,wechat',
        ])->message([
            'package_id.require' => '请选择充值套餐',
            'channel.require'    => '请选择支付方式',
            'channel.in'         => '支付方式无效',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        // 查找套餐
        $package = RechargePackage::find((int) $data['package_id']);
        if (!$package || $package->status !== 1) {
            return $this->error('套餐不存在或已下架', 404);
        }

        // 创建订单
        $orderNo = $this->paymentService->createOrder(
            userId: $this->userId(),
            type: $data['channel'],
            amount: (float) $package->amount,
            points: (int) $package->points,
            package: $package
        );

        // 调用支付网关
        try {
            $gateway = PayGatewayFactory::create($data['channel']);
            $result = $gateway->pay([
                'order_no' => $orderNo,
                'amount'   => (float) $package->amount,
                'subject'  => $package->name,
                'body'     => "帝国下载站 - {$package->name}",
                'user_id'  => $this->userId(),
            ]);

            if (!$result['success']) {
                return $this->error($result['message'], 500);
            }

            return $this->success([
                'order_no'  => $orderNo,
                'channel'   => $data['channel'],
                'pay_data'  => $result['data'],
                'pay_type'  => $result['type'] ?? 'html',
                'amount'    => $package->amount,
            ]);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * 查询支付状态
     * GET /api/v1/payment/query/:orderNo
     */
    public function query(string $orderNo): \think\Response
    {
        if (empty($orderNo)) {
            return $this->error('订单号不能为空', 422);
        }

        // 先从数据库查
        $order = $this->paymentService->getOrderByNo($orderNo);
        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        return $this->success([
            'order_no' => $orderNo,
            'status'   => $order['status'],
            'amount'   => $order['amount'],
            'paid_at'  => $order['paid_at'] ?? null,
        ]);
    }
}
