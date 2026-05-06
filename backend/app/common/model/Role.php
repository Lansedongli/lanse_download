<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 角色模型 (RBAC)
 */
class Role extends BaseModel
{
    protected $name = 'role';

    /** JSON 字段自动序列化 */
    protected $json = ['permissions'];
    protected $jsonAssoc = true;
}
