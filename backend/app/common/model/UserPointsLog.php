<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 用户点数变动日志模型
 */
class UserPointsLog extends BaseModel
{
    /** @var string 表名 */
    protected $name = 'user_points_log';

    /** @var bool 不自动写入时间戳(仅created_at) */
    protected $updateTime = false;
}
