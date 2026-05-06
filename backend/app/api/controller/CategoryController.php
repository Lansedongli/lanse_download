<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\controller\BaseController;
use app\common\model\Category as CategoryModel;
use think\App;

/**
 * 前台分类控制器
 * 返回分类树形结构供前端导航使用
 */
class CategoryController extends BaseController
{
    private readonly CategoryModel $categoryModel;

    public function __construct(App $app, CategoryModel $categoryModel)
    {
        parent::__construct($app);
        $this->categoryModel = $categoryModel;
    }

    /**
     * 获取完整分类树形结构
     * GET /api/v1/categories/tree
     */
    public function tree(): \think\Response
    {
        $tree = $this->categoryModel->getTree();

        return $this->success($tree);
    }
}
