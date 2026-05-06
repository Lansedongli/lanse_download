<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 充值套餐模型
 */
class RechargePackage extends Model
{
    protected $name = 'recharge_packages';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    /** 启用 */
    const STATUS_ENABLED = 1;
    /** 禁用 */
    const STATUS_DISABLED = 0;
}
