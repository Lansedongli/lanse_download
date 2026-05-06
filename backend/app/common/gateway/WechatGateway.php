<?php
declare(strict_types=1);

namespace app\common\gateway;

use WeChatPay\Builder;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Util\PemUtil;
use app\common\enum\PayChannelEnum;
use think\facade\Log;

/**
 * 微信支付网关
 * 使用官方 wechatpay/wechatpay (APIv3)
 */
class WechatGateway implements PayGatewayInterface
{
    private array $config;
    private bool $initialized = false;
    private $client;

    public function __construct()
    {
        $this->config = [
            'appid'       => env('WECHAT_APP_ID', ''),
            'mchid'       => env('WECHAT_MCH_ID', ''),
            'apiV3Key'    => env('WECHAT_API_V3_KEY', ''),
            'serialNo'    => env('WECHAT_SERIAL_NO', ''),
            'privateKey'  => env('WECHAT_PRIVATE_KEY_PATH', ''),
            'publicKey'   => env('WECHAT_PUBLIC_KEY_PATH', ''),
            'notifyUrl'   => env('WECHAT_NOTIFY_URL', ''),
        ];

        if (!empty($this->config['mchid']) && !empty($this->config['apiV3Key'])) {
            $this->init();
        }
    }

    private function init(): void
    {
        if ($this->initialized) return;

        try {
            $merchantId = $this->config['mchid'];

            // 从文件加载商户私钥
            $privateKey = PemUtil::loadPrivateKey($this->config['privateKey']);
            // 从文件加载平台证书
            $platformCertificate = PemUtil::loadCertificate($this->config['publicKey']);

            $this->client = Builder::factory([
                'mchid'      => $merchantId,
                'serial'     => $this->config['serialNo'],
                'privateKey' => $privateKey,
                'certs'      => [$this->config['serialNo'] => $platformCertificate],
            ]);

            $this->initialized = true;
        } catch (\Exception $e) {
            Log::error("[WechatPay] 初始化失败: {$e->getMessage()}");
        }
    }

    public function getChannel(): string
    {
        return PayChannelEnum::WECHAT->value;
    }

    /**
     * JSAPI/APP/Native/H5 支付 - 返回预支付信息
     */
    public function pay(array $order): array
    {
        if (!$this->initialized) {
            return ['success' => false, 'message' => '微信支付未配置'];
        }

        try {
            $body = [
                'appid'        => $this->config['appid'],
                'mchid'        => $this->config['mchid'],
                'description'  => $order['subject'] ?? '帝国下载站充值',
                'out_trade_no' => $order['order_no'],
                'notify_url'   => $this->config['notifyUrl'],
                'amount'       => [
                    'total'    => intval($order['amount'] * 100), // 单位：分
                    'currency' => 'CNY',
                ],
            ];

            // Native支付（扫码）—— 返回二维码链接
            $resp = $this->client->chain('v3/pay/transactions/native')
                ->post(['json' => $body]);

            $codeUrl = $resp->getData()['code_url'] ?? '';

            return [
                'success' => true,
                'data'    => ['code_url' => $codeUrl, 'order_no' => $order['order_no']],
                'type'    => 'qrcode',
            ];
        } catch (\Exception $e) {
            Log::error("[WechatPay] 创建支付失败: {$e->getMessage()}");
            return ['success' => false, 'message' => '支付创建失败: ' . $e->getMessage()];
        }
    }

    /**
     * 异步通知验签
     */
    public function notify(array $params): array
    {
        try {
            // 微信APIv3 回调是 JSON body，需从 php://input 读取
            $body = file_get_contents('php://input');

            // 验证签名
            $headers = [
                'Wechatpay-Serial'      => $_SERVER['HTTP_WECHATPAY_SERIAL'] ?? '',
                'Wechatpay-Signature'   => $_SERVER['HTTP_WECHATPAY_SIGNATURE'] ?? '',
                'Wechatpay-Timestamp'   => $_SERVER['HTTP_WECHATPAY_TIMESTAMP'] ?? '',
                'Wechatpay-Nonce'       => $_SERVER['HTTP_WECHATPAY_NONCE'] ?? '',
            ];

            $verified = Rsa::verify(
                $headers['Wechatpay-Timestamp'] . "\n" .
                $headers['Wechatpay-Nonce'] . "\n" .
                $body . "\n",
                $headers['Wechatpay-Signature'],
                PemUtil::loadCertificate($this->config['publicKey'])
            );

            if (!$verified) {
                return ['success' => false, 'message' => '微信签名验证失败'];
            }

            $data = json_decode($body, true);
            $resource = $data['resource'] ?? [];

            // 解密回调数据
            $decrypted = Rsa::decrypt(
                $resource['ciphertext'] ?? '',
                $resource['associated_data'] ?? '',
                $resource['nonce'] ?? '',
                PemUtil::loadPrivateKey($this->config['privateKey'])
            );

            $result = json_decode($decrypted, true);

            return [
                'success'  => true,
                'order_no' => $result['out_trade_no'] ?? '',
                'trade_no' => $result['transaction_id'] ?? '',
                'amount'   => (float) ($result['amount']['total'] ?? 0) / 100,
                'status'   => $result['trade_state'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::error("[WechatPay] 回调处理失败: {$e->getMessage()}");
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function verifyReturn(array $params): array
    {
        return ['success' => false, 'message' => '微信支付通过异步通知确认'];
    }

    public function query(string $orderNo): array
    {
        if (!$this->initialized) {
            return ['status' => 0, 'trade_no' => '', 'amount' => 0];
        }

        try {
            $resp = $this->client->chain("v3/pay/transactions/out-trade-no/{$orderNo}")
                ->get(['query' => ['mchid' => $this->config['mchid']]]);

            $data = $resp->getData();
            return [
                'status'   => $this->mapStatus($data['trade_state'] ?? ''),
                'trade_no' => $data['transaction_id'] ?? '',
                'amount'   => (float) ($data['amount']['total'] ?? 0) / 100,
            ];
        } catch (\Exception $e) {
            return ['status' => 0, 'trade_no' => '', 'amount' => 0];
        }
    }

    public function refund(string $orderNo, float $amount, string $refundNo): array
    {
        if (!$this->initialized) {
            return ['success' => false, 'message' => '微信支付未配置'];
        }

        try {
            $body = [
                'out_trade_no'  => $orderNo,
                'out_refund_no' => $refundNo,
                'amount'        => [
                    'refund'   => intval($amount * 100),
                    'total'    => intval($amount * 100),
                    'currency' => 'CNY',
                ],
            ];

            $this->client->chain('v3/refund/domestic/refunds')
                ->post(['json' => $body]);

            return ['success' => true, 'refund_no' => $refundNo, 'message' => '退款申请已提交'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => '退款失败: ' . $e->getMessage()];
        }
    }

    private function mapStatus(string $tradeState): int
    {
        return match ($tradeState) {
            'SUCCESS' => 1,
            'CLOSED', 'REVOKED', 'PAYERROR' => 2,
            default => 0,
        };
    }
}
