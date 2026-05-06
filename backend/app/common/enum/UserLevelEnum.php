<?php
declare(strict_types=1);

namespace app\common\enum;

/**
 * 会员等级枚举
 */
enum UserLevelEnum: int
{
    case NORMAL = 1;   // 普通会员
    case VIP    = 2;   // VIP会员
    case SVIP   = 3;   // SVIP会员
    case ADMIN  = 99;  // 管理员

    public function label(): string
    {
        return match($this) {
            self::NORMAL => '普通会员',
            self::VIP    => 'VIP会员',
            self::SVIP   => 'SVIP会员',
            self::ADMIN  => '管理员',
        };
    }

    public function maxDownloadDay(): int
    {
        return match($this) {
            self::NORMAL => 10,
            self::VIP    => 50,
            self::SVIP   => 0,   // 0=不限
            self::ADMIN  => 0,
        };
    }

    public function maxFavorites(): int
    {
        return match($this) {
            self::NORMAL => 100,
            self::VIP    => 500,
            self::SVIP   => 9999,
            self::ADMIN  => 9999,
        };
    }
}
