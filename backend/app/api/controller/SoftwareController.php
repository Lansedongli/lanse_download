<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\controller\BaseController;
use app\common\model\Category as CategoryModel;
use app\common\model\Software as SoftwareModel;
use think\App;
use think\facade\Validate;

/**
 * 前台下载对象控制器
 * 提供软件列表、详情、热门、推荐、搜索等公开接口
 */
class SoftwareController extends BaseController
{
    private readonly SoftwareModel $softwareModel;
    private readonly CategoryModel $categoryModel;

    public function __construct(App $app, SoftwareModel $softwareModel, CategoryModel $categoryModel)
    {
        parent::__construct($app);
        $this->softwareModel = $softwareModel;
        $this->categoryModel = $categoryModel;

        // 注入 AuthMiddleware 设置的用户信息
        if (isset($this->request->user)) {
            $this->currentUser = $this->request->user;
        }
    }

    /**
     * 软件列表（分类筛选/关键字搜索/排序/分页）
     * GET /api/v1/software?category_id=&keyword=&sort=&page=1&limit=20
     */
    public function index(): \think\Response
    {
        $categoryId = $this->request->get('category_id');
        $keyword    = $this->request->get('keyword');
        $sort       = $this->request->get('sort', 'id_desc');
        $page       = max(1, (int) $this->request->get('page', 1));
        $limit      = min(100, max(1, (int) $this->request->get('limit', 20)));

        $query = $this->softwareModel->alias('s')
            ->field('s.*')
            ->with(['category', 'tags'])
            ->where('s.status', 1); // 仅已发布

        // 分类筛选（含所有子分类）
        if ($categoryId !== null && $categoryId !== '') {
            $cid = (int) $categoryId;
            if ($cid > 0) {
                $childIds = $this->categoryModel->getChildIds($cid);
                $query->whereIn('s.category_id', $childIds);
            }
        }

        // 关键字搜索
        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('s.name', 'like', "%{$keyword}%")
                  ->whereOr('s.description', 'like', "%{$keyword}%");
            });
        }

        // 排序
        $query = $this->applySort($query, $sort);

        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();

        return $this->success([
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
            'list'  => $list,
        ]);
    }

    /**
     * 软件详情（含附件列表+标签）
     * GET /api/v1/software/:id
     */
    public function detail(int $id): \think\Response
    {
        $software = $this->softwareModel
            ->with(['category', 'attachments', 'tags'])
            ->find($id);

        if (!$software) {
            return $this->error('资源不存在', 404);
        }

        if ($software->status !== 1) {
            return $this->error('资源已下架', 404);
        }

        return $this->success($software->toArray());
    }

    /**
     * 热门排行
     * GET /api/v1/software/hot?limit=10
     */
    public function hot(): \think\Response
    {
        $limit = min(50, max(1, (int) $this->request->get('limit', 10)));

        $list = SoftwareModel::hot($limit)
            ->with(['category', 'tags'])
            ->select()
            ->toArray();

        return $this->success([
            'limit' => $limit,
            'list'  => $list,
        ]);
    }

    /**
     * 推荐列表
     * GET /api/v1/software/recommend?limit=10
     */
    public function recommend(): \think\Response
    {
        $limit = min(50, max(1, (int) $this->request->get('limit', 10)));

        $list = SoftwareModel::recommend($limit)
            ->with(['category', 'tags'])
            ->select()
            ->toArray();

        return $this->success([
            'limit' => $limit,
            'list'  => $list,
        ]);
    }

    /**
     * 全文搜索
     * GET /api/v1/software/search?q=&page=1&limit=20
     */
    public function search(): \think\Response
    {
        $q     = $this->request->get('q', '');
        $page  = max(1, (int) $this->request->get('page', 1));
        $limit = min(50, max(1, (int) $this->request->get('limit', 20)));

        $validate = Validate::rule([
            'q' => 'require|min:1',
        ])->message([
            'q.require' => '搜索关键词不能为空',
            'q.min'     => '搜索关键词至少1个字符',
        ]);

        if (!$validate->check(['q' => $q])) {
            return $this->error($validate->getError(), 422);
        }

        $query = $this->softwareModel->alias('s')
            ->field('s.*')
            ->with(['category', 'tags'])
            ->where('s.status', 1)
            ->where(function ($qBuilder) use ($q) {
                $qBuilder->where('s.name', 'like', "%{$q}%")
                         ->whereOr('s.description', 'like', "%{$q}%")
                         ->whereOr('s.version', 'like', "%{$q}%");
            })
            ->order('s.id desc');

        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();

        return $this->success([
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
            'keyword' => $q,
            'list'    => $list,
        ]);
    }

    /**
     * 应用排序规则
     */
    private function applySort($query, string $sort): mixed
    {
        return match ($sort) {
            'download_desc' => $query->order('s.download_count desc'),
            'download_asc'  => $query->order('s.download_count asc'),
            'newest'        => $query->order('s.id desc'),
            'oldest'        => $query->order('s.id asc'),
            'sort_asc'      => $query->order('s.sort asc'),
            'sort_desc'     => $query->order('s.sort desc'),
            default         => $query->order('s.id desc'), // id_desc
        };
    }
}
