<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 基础模型类
 * 所有模型继承此类，统一软删除、时间戳、字段格式化
 */
abstract class BaseModel extends Model
{
    /** @var bool 自动写入时间戳 */
    protected $autoWriteTimestamp = true;

    /** @var string 创建时间字段 */
    protected $createTime = 'created_at';

    /** @var string 更新时间字段 */
    protected $updateTime = 'updated_at';

    /** @var string 软删除字段(子类按需开启) */
    protected $deleteTime = false;

    /** @var string 默认排序 */
    protected $defaultOrder = 'id desc';

    /**
     * 通用分页查询
     */
    public function paginateList(array $where = [], int $page = 1, int $limit = 20, string $order = ''): array
    {
        $query = $this->where($where);
        $total = $query->count();
        $list = $query->order($order ?: $this->defaultOrder)
                       ->page($page, $limit)
                       ->select()
                       ->toArray();

        return [
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
            'list'  => $list,
        ];
    }

    /**
     * 通用状态切换
     */
    public function toggleStatus(int $id, int $status): bool
    {
        return (bool) $this->where('id', $id)->update(['status' => $status]);
    }
}
