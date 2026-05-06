<?php
declare(strict_types=1);

namespace app\common\enum;

/**
 * 通用状态枚举
 */
enum StatusEnum: int
{
    case DISABLED = 0;    // 禁用
    case ENABLED  = 1;    // 启用
    case DELETED  = -1;   // 已删除

    public function label(): string
    {
        return match($this) {
            self::DISABLED => '禁用',
            self::ENABLED  => '启用',
            self::DELETED  => '已删除',
        };
    }
}
