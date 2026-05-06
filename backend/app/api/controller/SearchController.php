<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\controller\BaseController;
use app\common\service\SearchService;
use think\App;
use think\facade\Validate;

/**
 * 全站搜索控制器
 * 提供全文搜索和热门搜索词接口（均为公开接口）
 */
class SearchController extends BaseController
{
    private readonly SearchService $searchService;

    public function __construct(App $app, SearchService $searchService)
    {
        parent::__construct($app);
        $this->searchService = $searchService;
    }

    /**
     * 全站搜索
     * GET /api/v1/search?keyword=xxx&type=all&page=1&pageSize=20
     *
     * @param string $keyword  搜索关键词（必填）
     * @param string $type     搜索类型: all(默认) / software / category
     * @param int    $page     页码
     * @param int    $pageSize 每页条数
     */
    public function index(): \think\Response
    {
        $keyword  = $this->request->get('keyword', '');
        $type     = $this->request->get('type', 'all');
        $page     = (int) $this->request->get('page', 1);
        $pageSize = (int) $this->request->get('pageSize', 20);

        // 参数校验
        $validate = Validate::rule([
            'keyword' => 'require|min:1',
        ])->message([
            'keyword.require' => '搜索关键词不能为空',
            'keyword.min'     => '搜索关键词至少1个字符',
        ]);

        if (!$validate->check(['keyword' => $keyword])) {
            return $this->error($validate->getError(), 422);
        }

        // 校验 type 参数
        if (!in_array($type, ['all', 'software', 'category'])) {
            return $this->error('搜索类型无效，可选值: all/software/category', 422);
        }

        // 执行搜索
        $result = $this->searchService->search($keyword, $type, $page, $pageSize);

        // 异步记录搜索日志（不阻塞响应）
        $this->searchService->logSearch($keyword);

        return $this->success([
            'keyword'  => $keyword,
            'type'     => $type,
            'total'    => $result['total'],
            'page'     => $result['page'],
            'pageSize' => $result['pageSize'],
            'data'     => $result['data'],
        ]);
    }

    /**
     * 热门搜索词
     * GET /api/v1/search/hot?limit=10
     */
    public function hot(): \think\Response
    {
        $limit = min(50, max(1, (int) $this->request->get('limit', 10)));

        $keywords = $this->searchService->hotKeywords($limit);

        return $this->success([
            'limit'    => $limit,
            'keywords' => $keywords,
        ]);
    }
}
