<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 后台管理员模型
 */
class AdminUser extends BaseModel
{
    /** @var string 表名 */
    protected $name = 'admin_user';

    /** @var array 隐藏字段 */
    protected $hidden = ['password'];
}
