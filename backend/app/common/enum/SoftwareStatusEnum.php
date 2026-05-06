<?php
declare(strict_types=1);

namespace app\common\enum;

/**
 * 软件/下载对象状态枚举
 */
enum SoftwareStatusEnum: int
{
    case PENDING  = 0;   // 待审核
    case PUBLISHED = 1;  // 已发布
    case REJECTED = 2;   // 已退回
    case DRAFT    = 3;   // 草稿

    public function label(): string
    {
        return match($this) {
            self::PENDING   => '待审核',
            self::PUBLISHED => '已发布',
            self::REJECTED  => '已退回',
            self::DRAFT     => '草稿',
        };
    }
}
