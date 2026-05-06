<?php
declare(strict_types=1);

namespace app\common\gateway;

use Alipay\EasySDK\Kernel\Factory;
use Alipay\EasySDK\Kernel\Config;
use app\common\enum\PayChannelEnum;
use think\facade\Log;

/**
 * 支付宝支付网关
 * 使用官方 alipaysdk/easysdk
 */
class AlipayGateway implements PayGatewayInterface
{
    private array $config;
    private bool $initialized = false;

    public function __construct()
    {
        $this->config = [
            'protocol'     => 'https',
            'gatewayHost'  => 'openapi.alipay.com',
            'signType'     => 'RSA2',
            'appId'        => env('ALIPAY_APP_ID', ''),
            'merchantPrivateKey' => env('ALIPAY_PRIVATE_KEY', ''),
            'alipayPublicKey'    => env('ALIPAY_PUBLIC_KEY', ''),
            'notifyUrl'    => env('ALIPAY_NOTIFY_URL', ''),
        ];

        if (!empty($this->config['appId']) && !empty($this->config['merchantPrivateKey'])) {
            $this->init();
        }
    }

    private function init(): void
    {
        if ($this->initialized) return;

        $options = new Config();
        $options->protocol    = $this->config['protocol'];
        $options->gatewayHost = $this->config['gatewayHost'];
        $options->signType    = $this->config['signType'];
        $options->appId       = $this->config['appId'];
        $options->merchantPrivateKey = $this->config['merchantPrivateKey'];
        $options->alipayPublicKey    = $this->config['alipayPublicKey'];
        $options->notifyUrl          = $this->config['notifyUrl'];

        Factory::setOptions($options);
        $this->initialized = true;
    }

    public function getChannel(): string
    {
        return PayChannelEnum::ALIPAY->value;
    }

    /**
     * 创建支付（返回支付表单HTML或跳转URL）
     */
    public function pay(array $order): array
    {
        if (!$this->initialized) {
            return ['success' => false, 'message' => '支付宝未配置'];
        }

        try {
            $subject = $order['subject'] ?? '帝国下载站充值';
            $body    = $order['body'] ?? '账户充值';

            $result = Factory::payment()
                ->page()
                ->optional('passback_params', $order['order_no'])
                ->pay($subject, $order['order_no'], (string) $order['amount']);

            // EasySDK 返回的是 HTML form 字符串
            return [
                'success' => true,
                'data'    => $result->body,
                'type'    => 'html',
            ];
        } catch (\Exception $e) {
            Log::error("[Alipay] 创建支付失败: {$e->getMessage()}");
            return ['success' => false, 'message' => '支付创建失败: ' . $e->getMessage()];
        }
    }

    /**
     * 异步通知验签和处理
     */
    public function notify(array $params): array
    {
        try {
            $result = Factory::payment()->common()->verifyNotify($params);

            if (!$result) {
                return ['success' => false, 'message' => '签名验证失败'];
            }

            return [
                'success'  => true,
                'order_no' => $params['out_trade_no'] ?? '',
                'trade_no' => $params['trade_no'] ?? '',
                'amount'   => (float) ($params['total_amount'] ?? 0),
                'status'   => $params['trade_status'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::error("[Alipay] 回调处理失败: {$e->getMessage()}");
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 同步跳转验签
     */
    public function verifyReturn(array $params): array
    {
        try {
            $result = Factory::payment()->common()->verifyNotify($params);
            return [
                'success'  => $result,
                'order_no' => $params['out_trade_no'] ?? '',
                'message'  => $result ? '支付成功' : '签名验证失败',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 查询支付状态
     */
    public function query(string $orderNo): array
    {
        if (!$this->initialized) {
            return ['status' => 0, 'trade_no' => '', 'amount' => 0];
        }

        try {
            $result = Factory::payment()->common()->query($orderNo);

            return [
                'status'   => $this->mapStatus($result->tradeStatus ?? ''),
                'trade_no' => $result->tradeNo ?? '',
                'amount'   => (float) ($result->totalAmount ?? 0),
            ];
        } catch (\Exception $e) {
            Log::error("[Alipay] 查询失败: {$e->getMessage()}");
            return ['status' => 0, 'trade_no' => '', 'amount' => 0];
        }
    }

    /**
     * 退款
     */
    public function refund(string $orderNo, float $amount, string $refundNo): array
    {
        if (!$this->initialized) {
            return ['success' => false, 'message' => '支付宝未配置'];
        }

        try {
            Factory::payment()->common()->refund($orderNo, (string) $amount, $refundNo);
            return ['success' => true, 'refund_no' => $refundNo, 'message' => '退款申请已提交'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => '退款失败: ' . $e->getMessage()];
        }
    }

    private function mapStatus(string $tradeStatus): int
    {
        return match ($tradeStatus) {
            'TRADE_SUCCESS', 'TRADE_FINISHED' => 1,
            'TRADE_CLOSED' => 2,
            default => 0,
        };
    }
}
