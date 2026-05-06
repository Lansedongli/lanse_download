<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 会员用户模型
 */
class User extends BaseModel
{
    /** @var string 表名 */
    protected $name = 'user';

    /** @var string 主键 */
    protected $pk = 'id';

    /** @var bool 软删除 */
    protected $deleteTime = 'deleted_at';

    /** @var string 默认软删除字段 */
    protected $defaultSoftDelete = 0;

    /** @var array 隐藏字段 */
    protected $hidden = ['password', 'deleted_at'];

    /** @var array 只读字段 */
    protected $readonly = ['username'];

    /**
     * 用户收藏的软件
     */
    public function favorites()
    {
        return $this->belongsToMany(Software::class, 'user_favorite', 'software_id', 'user_id');
    }
}
