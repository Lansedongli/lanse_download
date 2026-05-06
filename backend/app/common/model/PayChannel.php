<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 支付渠道配置模型
 */
class PayChannel extends Model
{
    protected $name = 'pay_channels';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    /** 启用 */
    const STATUS_ENABLED = 1;
    /** 禁用 */
    const STATUS_DISABLED = 0;
}
