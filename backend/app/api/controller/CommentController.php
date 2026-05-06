<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\controller\BaseController;
use app\common\model\Comment as CommentModel;
use think\App;
use think\facade\Validate;

/**
 * 评论控制器
 * 处理软件评论列表查看和发表评论
 */
class CommentController extends BaseController
{
    private readonly CommentModel $commentModel;

    public function __construct(App $app, CommentModel $commentModel)
    {
        parent::__construct($app);
        $this->commentModel = $commentModel;

        // 注入 AuthMiddleware 设置的用户信息
        if (isset($this->request->user)) {
            $this->currentUser = $this->request->user;
        }
    }

    /**
     * 评论列表
     * GET /api/v1/comments?software_id=&page=1&limit=20
     */
    public function list(): \think\Response
    {
        $softwareId = (int) $this->request->get('software_id', 0);
        $page       = max(1, (int) $this->request->get('page', 1));
        $limit      = min(100, max(1, (int) $this->request->get('limit', 20)));

        if ($softwareId <= 0) {
            return $this->error('软件ID不能为空', 422);
        }

        $result = $this->commentModel
            ->where('software_id', $softwareId)
            ->where('status', 1) // 仅展示已审核
            ->order('id desc')
            ->paginate(['page' => $page, 'list_rows' => $limit]);

        return $this->success([
            'total' => $result->total(),
            'page'  => $page,
            'limit' => $limit,
            'list'  => $result->items(),
        ]);
    }

    /**
     * 发表评论（需登录）
     * POST /api/v1/comments
     *
     * @body software_id int    软件ID
     * @body content     string 评论内容
     * @body rating      int    评分(1-5,可选)
     * @body parent_id   int    父评论ID(回复时使用,可选)
     */
    public function create(): \think\Response
    {
        $data = $this->request->post();

        $validate = Validate::rule([
            'software_id' => 'require|integer|>:0',
            'content'     => 'require|length:2,2000',
            'rating'      => 'integer|between:1,5',
            'parent_id'   => 'integer|>=:0',
        ])->message([
            'software_id.require' => '软件ID不能为空',
            'software_id.integer' => '软件ID必须为整数',
            'content.require'     => '评论内容不能为空',
            'content.length'      => '评论内容需在2-2000个字符之间',
            'rating.integer'      => '评分必须为整数',
            'rating.between'      => '评分需在1-5之间',
            'parent_id.integer'   => '父评论ID必须为整数',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        // 验证软件存在且已发布
        $software = \app\common\model\Software::where('id', (int) $data['software_id'])
            ->where('status', 1)
            ->find();

        if (!$software) {
            return $this->error('资源不存在或已下架', 404);
        }

        // 如果是回复，验证父评论存在
        if (!empty($data['parent_id'])) {
            $parent = $this->commentModel
                ->where('id', (int) $data['parent_id'])
                ->where('software_id', (int) $data['software_id'])
                ->where('status', 1)
                ->find();

            if (!$parent) {
                return $this->error('被回复的评论不存在', 404);
            }
        }

        $comment = $this->commentModel->create([
            'software_id' => (int) $data['software_id'],
            'user_id'     => $this->userId(),
            'content'     => trim($data['content']),
            'rating'      => (int) ($data['rating'] ?? 0),
            'parent_id'   => (int) ($data['parent_id'] ?? 0),
            'status'      => 1, // 默认审核通过
            'ip'          => $this->request->ip(),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        return $this->success($comment->toArray(), '评论发表成功');
    }
}
