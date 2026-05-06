<?php
declare(strict_types=1);

namespace app\common\enum;

/**
 * 支付渠道枚举
 */
enum PayChannelEnum: string
{
    case ALIPAY = 'alipay';
    case WECHAT = 'wechat';
    case UNIONPAY = 'unionpay';

    public function label(): string
    {
        return match($this) {
            self::ALIPAY  => '支付宝',
            self::WECHAT  => '微信支付',
            self::UNIONPAY => '银联支付',
        };
    }

    public function isEnabled(): bool
    {
        return match($this) {
            self::ALIPAY  => !empty(env('ALIPAY_APP_ID')),
            self::WECHAT  => !empty(env('WECHAT_APP_ID')),
            self::UNIONPAY => false,
        };
    }
}
