<?php
declare(strict_types=1);

namespace app\common\gateway;

use app\common\enum\PayChannelEnum;
use RuntimeException;

/**
 * 支付网关工厂
 */
class PayGatewayFactory
{
    private static array $instances = [];

    /**
     * 获取支付网关实例
     */
    public static function create(string $channel): PayGatewayInterface
    {
        $key = strtolower($channel);

        if (isset(self::$instances[$key])) {
            return self::$instances[$key];
        }

        self::$instances[$key] = match ($key) {
            PayChannelEnum::ALIPAY->value => new AlipayGateway(),
            PayChannelEnum::WECHAT->value => new WechatGateway(),
            default => throw new RuntimeException("不支持的支付渠道: {$channel}"),
        };

        return self::$instances[$key];
    }

    /**
     * 获取所有可用渠道
     */
    public static function enabledChannels(): array
    {
        $channels = [];
        foreach (PayChannelEnum::cases() as $channel) {
            try {
                $gateway = self::create($channel->value);
                $channels[] = [
                    'code'  => $channel->value,
                    'label' => $channel->label(),
                ];
            } catch (RuntimeException) {
                continue;
            }
        }
        return $channels;
    }
}
