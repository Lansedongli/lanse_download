<?php
declare(strict_types=1);

namespace app\common\enum;

/**
 * 充值类型枚举
 */
enum RechargeTypeEnum: string
{
    case POINT_CARD  = 'point_card';     // 点卡充值
    case ONLINE_BANK = 'online_bank';    // 网银
    case ALIPAY      = 'alipay';        // 支付宝
    case WECHAT      = 'wechat';        // 微信支付
    case ADMIN       = 'admin';         // 管理员手动

    public function label(): string
    {
        return match($this) {
            self::POINT_CARD  => '点卡充值',
            self::ONLINE_BANK => '网银充值',
            self::ALIPAY      => '支付宝',
            self::WECHAT      => '微信支付',
            self::ADMIN       => '管理员手动',
        };
    }
}
