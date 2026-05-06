<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\controller\BaseController;
use app\common\gateway\PayGatewayFactory;
use app\common\service\PaymentService;
use think\App;
use think\facade\Log;

/**
 * 支付回调控制器
 * 接收支付宝/微信支付的异步通知（无需认证，但需验签）
 */
class NotifyController extends BaseController
{
    private readonly PaymentService $paymentService;

    public function __construct(App $app, PaymentService $paymentService)
    {
        parent::__construct($app);
        $this->paymentService = $paymentService;
    }

    /**
     * 支付宝异步通知
     * POST /api/v1/notify/alipay
     */
    public function alipay(): \think\Response
    {
        $params = $this->request->post();

        Log::info("[支付回调] 支付宝通知: " . json_encode($params, JSON_UNESCAPED_UNICODE));

        try {
            $gateway = PayGatewayFactory::create('alipay');
            $result = $gateway->notify($params);

            if (!$result['success']) {
                Log::warning("[支付回调] 支付宝验签失败: " . ($result['message'] ?? ''));
                return response('fail');
            }

            // 只处理支付成功的通知
            if (($result['status'] ?? '') === 'TRADE_SUCCESS') {
                $this->paymentService->handleCallback(
                    orderNo: $result['order_no'],
                    tradeNo: $result['trade_no'],
                    success: true
                );
                Log::info("[支付回调] 支付宝充值成功: order={$result['order_no']} trade={$result['trade_no']}");
            }

            return response('success');
        } catch (\Exception $e) {
            Log::error("[支付回调] 支付宝处理异常: {$e->getMessage()}");
            return response('fail');
        }
    }

    /**
     * 微信支付异步通知
     * POST /api/v1/notify/wechat
     */
    public function wechat(): \think\Response
    {
        Log::info("[支付回调] 微信支付通知");

        try {
            $gateway = PayGatewayFactory::create('wechat');
            $result = $gateway->notify([]); // 内部从 php://input 读取

            if (!$result['success']) {
                Log::warning("[支付回调] 微信验签失败: " . ($result['message'] ?? ''));
                return response(json_encode([
                    'code'    => 'FAIL',
                    'message' => $result['message'] ?? '签名验证失败',
                ]), 400)->header(['Content-Type' => 'application/json']);
            }

            // 只处理支付成功的通知
            if (($result['status'] ?? '') === 'SUCCESS') {
                $this->paymentService->handleCallback(
                    orderNo: $result['order_no'],
                    tradeNo: $result['trade_no'],
                    success: true
                );
                Log::info("[支付回调] 微信充值成功: order={$result['order_no']}");
            }

            // 微信回调必须返回 200 + 特定 JSON
            return response(json_encode(['code' => 'SUCCESS', 'message' => '成功']))
                ->header(['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            Log::error("[支付回调] 微信处理异常: {$e->getMessage()}");
            return response(json_encode(['code' => 'FAIL', 'message' => $e->getMessage()]), 500)
                ->header(['Content-Type' => 'application/json']);
        }
    }

    /**
     * 支付宝同步跳转
     * GET /api/v1/notify/return/alipay
     */
    public function alipayReturn(): \think\Response
    {
        $params = $this->request->get();

        try {
            $gateway = PayGatewayFactory::create('alipay');
            $result = $gateway->verifyReturn($params);

            if ($result['success']) {
                // 重定向到前端支付成功页
                return redirect('/#/pay/success?order_no=' . $result['order_no']);
            }
            return redirect('/#/pay/fail?msg=' . urlencode($result['message']));
        } catch (\Exception $e) {
            return redirect('/#/pay/fail?msg=' . urlencode($e->getMessage()));
        }
    }
}
