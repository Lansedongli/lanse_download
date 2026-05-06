<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\BaseController;
use app\common\model\Category;
use think\App;
use think\facade\Validate;

/**
 * 无限级分类管理
 */
class CategoryController extends BaseController
{
    private readonly Category $categoryModel;

    public function __construct(App $app, Category $categoryModel)
    {
        parent::__construct($app);
        $this->categoryModel = $categoryModel;
    }

    /**
     * 获取分类树形结构
     * GET /admin/api/category/tree?parent_id=0
     */
    public function tree(): \think\Response
    {
        $parentId = (int) $this->request->get('parent_id', 0);
        $tree = $this->categoryModel->getTree($parentId);

        return $this->success($tree);
    }

    /**
     * 新增分类
     * POST /admin/api/category
     */
    public function create(): \think\Response
    {
        $data = $this->request->post();

        $validate = Validate::rule([
            'name'      => 'require|max:100',
            'parent_id' => 'integer',
            'sort'      => 'integer',
            'status'    => 'in:0,1',
        ])->message([
            'name.require' => '分类名称不能为空',
            'name.max'     => '分类名称不能超过100个字符',
            'status.in'    => '状态值无效',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        // 如果指定了父分类，检查其是否存在
        if (!empty($data['parent_id'])) {
            $parent = $this->categoryModel->find($data['parent_id']);
            if (!$parent) {
                return $this->error('父分类不存在', 404);
            }
        }

        $category = $this->categoryModel->save([
            'name'       => $data['name'],
            'parent_id'  => $data['parent_id'] ?? 0,
            'sort'       => $data['sort'] ?? 0,
            'status'     => $data['status'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$category) {
            return $this->error('新增分类失败');
        }

        return $this->success(
            $this->categoryModel->toArray(),
            '新增分类成功'
        );
    }

    /**
     * 修改分类
     * PUT /admin/api/category/:id
     */
    public function update(int $id): \think\Response
    {
        $category = $this->categoryModel->find($id);
        if (!$category) {
            return $this->error('分类不存在', 404);
        }

        $data = $this->request->put();

        $validate = Validate::rule([
            'name'      => 'max:100',
            'parent_id' => 'integer',
            'sort'      => 'integer',
            'status'    => 'in:0,1',
        ])->message([
            'name.max'  => '分类名称不能超过100个字符',
            'status.in' => '状态值无效',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        // 父分类不能设为自身或其子分类
        if (isset($data['parent_id'])) {
            $pid = (int) $data['parent_id'];
            if ($pid === $id) {
                return $this->error('父分类不能是自身', 422);
            }
            if ($pid > 0) {
                $childIds = $this->categoryModel->getChildIds($id);
                if (in_array($pid, $childIds, true)) {
                    return $this->error('父分类不能是其子分类', 422);
                }
            }
        }

        $allowFields = ['name', 'parent_id', 'sort', 'status'];
        foreach ($allowFields as $field) {
            if (isset($data[$field])) {
                $category->{$field} = $data[$field];
            }
        }
        $category->save();

        return $this->success($category->toArray(), '修改分类成功');
    }

    /**
     * 删除分类
     * DELETE /admin/api/category/:id
     */
    public function delete(int $id): \think\Response
    {
        $category = $this->categoryModel->find($id);
        if (!$category) {
            return $this->error('分类不存在', 404);
        }

        // 检查是否存在子分类
        $hasChildren = $this->categoryModel->where('parent_id', $id)->count();
        if ($hasChildren > 0) {
            return $this->error('该分类下存在子分类，无法删除', 422);
        }

        $category->delete();

        return $this->success([], '删除分类成功');
    }
}
