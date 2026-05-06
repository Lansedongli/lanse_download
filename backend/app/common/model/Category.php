<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 无限级分类模型
 */
class Category extends BaseModel
{
    protected $name = 'category';

    /**
     * 获取树形结构
     */
    public function getTree(int $parentId = 0, string $order = 'sort asc,id asc'): array
    {
        $list = $this->where('status', 1)
                     ->order($order)
                     ->select()
                     ->toArray();

        return $this->buildTree($list, $parentId);
    }

    /**
     * 递归构建树
     */
    private function buildTree(array $list, int $parentId = 0): array
    {
        $tree = [];
        foreach ($list as $item) {
            if ($item['parent_id'] == $parentId) {
                $item['children'] = $this->buildTree($list, $item['id']);
                $tree[] = $item;
            }
        }
        return $tree;
    }

    /**
     * 获取某个分类的所有子分类ID（含自身）
     */
    public function getChildIds(int $id): array
    {
        $ids = [$id];
        $children = $this->where('parent_id', $id)->column('id');
        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->getChildIds($childId));
        }
        return $ids;
    }
}
