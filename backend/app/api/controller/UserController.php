<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\controller\BaseController;
use app\common\model\User as UserModel;
use app\common\model\UserPointsLog as PointsLogModel;
use think\App;
use think\facade\Validate;

/**
 * 用户个人中心控制器
 * 需认证访问 (AuthMiddleware)
 */
class UserController extends BaseController
{
    private UserModel $userModel;
    private PointsLogModel $pointsLogModel;

    public function __construct(App $app, UserModel $userModel, PointsLogModel $pointsLogModel)
    {
        parent::__construct($app);
        $this->userModel      = $userModel;
        $this->pointsLogModel = $pointsLogModel;
    }

    /**
     * 获取当前用户信息
     * GET /api/v1/user/me
     */
    public function profile(): \think\Response
    {
        $user = $this->userModel->field('id,username,email,mobile,nickname,avatar,group_id,points,expire_time,total_download,today_download,created_at')
                                ->find($this->userId());

        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        return $this->success($user->toArray());
    }

    /**
     * 修改个人信息
     * PUT /api/v1/user/me
     */
    public function updateProfile(): \think\Response
    {
        $data = $this->request->put();

        $validate = Validate::rule([
            'nickname' => 'max:100',
            'mobile'   => 'mobile',
            'avatar'   => 'url',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        $update = [];
        if (isset($data['nickname'])) $update['nickname'] = $data['nickname'];
        if (isset($data['mobile']))   $update['mobile']   = $data['mobile'];
        if (isset($data['avatar']))   $update['avatar']   = $data['avatar'];

        if (empty($update)) {
            return $this->error('无修改内容', 422);
        }

        $this->userModel->where('id', $this->userId())->update($update);

        return $this->success([], '修改成功');
    }

    /**
     * 修改密码
     * PUT /api/v1/user/password
     */
    public function changePassword(): \think\Response
    {
        $data = $this->request->put();

        $validate = Validate::rule([
            'old_password' => 'require',
            'new_password' => 'require|length:6,32',
        ])->message([
            'old_password.require' => '旧密码不能为空',
            'new_password.require' => '新密码不能为空',
            'new_password.length'  => '新密码长度需在6-32字符之间',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        $user = $this->userModel->find($this->userId());
        if (!password_verify($data['old_password'], $user->password)) {
            return $this->error('旧密码不正确', 403);
        }

        $user->password = password_hash($data['new_password'], PASSWORD_BCRYPT);
        $user->save();

        return $this->success([], '密码修改成功');
    }

    /**
     * 点数变动记录
     * GET /api/v1/user/points-log?page=1&limit=20
     */
    public function pointsLog(): \think\Response
    {
        $page  = (int) $this->request->get('page', 1);
        $limit = (int) $this->request->get('limit', 20);

        $result = $this->pointsLogModel->where('user_id', $this->userId())
                                       ->order('id desc')
                                       ->paginate(['page' => $page, 'list_rows' => $limit]);

        return $this->success([
            'total' => $result->total(),
            'page'  => $page,
            'limit' => $limit,
            'list'  => $result->items(),
        ]);
    }
}
