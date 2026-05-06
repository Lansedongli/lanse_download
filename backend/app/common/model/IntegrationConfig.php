<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 万能会员整合接口配置模型
 * 存储第三方MySQL用户系统的连接配置
 */
class IntegrationConfig extends BaseModel
{
    /** @var string 表名 */
    protected $name = 'integration_config';

    /** @var string 主键 */
    protected $pk = 'id';

    /** @var array 隐藏字段 */
    protected $hidden = ['db_password'];

    /** @var array JSON类型字段 */
    protected $json = [];
}
