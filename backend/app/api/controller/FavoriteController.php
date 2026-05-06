<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\controller\BaseController;
use app\common\model\Software as SoftwareModel;
use app\common\model\User as UserModel;
use think\App;
use think\facade\Validate;

/**
 * 收藏控制器
 * 处理用户收藏添加、取消、列表查询
 * 需认证访问 (AuthMiddleware)
 */
class FavoriteController extends BaseController
{
    private readonly UserModel $userModel;
    private readonly SoftwareModel $softwareModel;

    public function __construct(App $app, UserModel $userModel, SoftwareModel $softwareModel)
    {
        parent::__construct($app);
        $this->userModel     = $userModel;
        $this->softwareModel = $softwareModel;

        // 注入 AuthMiddleware 设置的用户信息
        if (isset($this->request->user)) {
            $this->currentUser = $this->request->user;
        }
    }

    /**
     * 我的收藏列表（需登录）
     * GET /api/v1/favorites?page=1&limit=20
     */
    public function list(): \think\Response
    {
        $page  = max(1, (int) $this->request->get('page', 1));
        $limit = min(100, max(1, (int) $this->request->get('limit', 20)));

        $user = $this->userModel->find($this->userId());
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $result = $user->favorites()
            ->with(['category', 'tags'])
            ->where('software.status', 1)
            ->order('user_favorite.created_at desc')
            ->paginate(['page' => $page, 'list_rows' => $limit]);

        return $this->success([
            'total' => $result->total(),
            'page'  => $page,
            'limit' => $limit,
            'list'  => $result->items(),
        ]);
    }

    /**
     * 添加收藏（需登录）
     * POST /api/v1/favorites
     *
     * @body software_id int 软件ID
     */
    public function add(): \think\Response
    {
        $data = $this->request->post();

        $validate = Validate::rule([
            'software_id' => 'require|integer|>:0',
        ])->message([
            'software_id.require' => '软件ID不能为空',
            'software_id.integer' => '软件ID必须为整数',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        $softwareId = (int) $data['software_id'];

        // 验证软件存在且已发布
        $software = $this->softwareModel
            ->where('id', $softwareId)
            ->where('status', 1)
            ->find();

        if (!$software) {
            return $this->error('资源不存在或已下架', 404);
        }

        $user = $this->userModel->find($this->userId());
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        // 检查是否已收藏
        $exists = $user->favorites()->where('software_id', $softwareId)->find();
        if ($exists) {
            return $this->error('已收藏该资源', 409);
        }

        $user->favorites()->attach($softwareId);

        return $this->success([], '收藏成功');
    }

    /**
     * 取消收藏（需登录）
     * DELETE /api/v1/favorites/:id
     *
     * @param int $id 软件ID
     */
    public function remove(int $id): \think\Response
    {
        if ($id <= 0) {
            return $this->error('软件ID无效', 422);
        }

        $user = $this->userModel->find($this->userId());
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        // 检查是否已收藏
        $exists = $user->favorites()->where('software_id', $id)->find();
        if (!$exists) {
            return $this->error('未收藏该资源', 404);
        }

        $user->favorites()->detach($id);

        return $this->success([], '已取消收藏');
    }
}
