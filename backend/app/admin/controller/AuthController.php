<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\BaseController;
use app\common\model\AdminUser as AdminUserModel;
use app\common\service\JwtService;
use think\App;
use think\facade\Db;
use think\facade\Validate;

/**
 * 管理员认证控制器
 */
class AuthController extends BaseController
{
    private AdminUserModel $adminModel;
    private JwtService $jwtService;

    public function __construct(App $app, AdminUserModel $adminModel, JwtService $jwtService)
    {
        parent::__construct($app);
        $this->adminModel = $adminModel;
        $this->jwtService = $jwtService;
    }

    /**
     * 管理员登录
     * POST /admin/api/auth/login
     */
    public function login(): \think\Response
    {
        $data = $this->request->post();

        $validate = Validate::rule([
            'username' => 'require',
            'password' => 'require',
        ])->message([
            'username.require' => '用户名不能为空',
            'password.require' => '密码不能为空',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        $admin = $this->adminModel->where('username', $data['username'])->find();
        if (!$admin || !password_verify($data['password'], $admin->password)) {
            return $this->error('用户名或密码错误', 401);
        }

        if ($admin->status !== 1) {
            return $this->error('账号已被禁用', 403);
        }

        // 更新登录信息
        $admin->login_ip   = $this->request->ip();
        $admin->login_time = date('Y-m-d H:i:s');
        $admin->login_count = ($admin->login_count ?? 0) + 1;
        $admin->save();

        // 记录登录日志
        try {
            Db::table('user_login_log')->insert([
                'user_id'  => $admin->id,
                'username' => $admin->username,
                'ip'       => $this->request->ip(),
                'user_agent' => mb_substr($this->request->header('User-Agent', ''), 0, 500),
                'status'    => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) { /* 日志失败不影响主流程 */ }

        // 签发 Token (admin guard)
        $tokens = $this->jwtService->createToken([
            'id'       => $admin->id,
            'username' => $admin->username,
            'role_id'  => $admin->role_id,
            'group_id'  => 0,
            'level'     => 99,
        ], 'admin');

        return $this->success([
            'user'   => [
                'id'       => $admin->id,
                'username' => $admin->username,
                'nickname' => $admin->nickname,
                'role_id'  => $admin->role_id,
            ],
            'tokens' => $tokens,
        ], '登录成功');
    }

    /**
     * 管理员退出
     * POST /admin/api/auth/logout
     */
    public function logout(): \think\Response
    {
        $header = $this->request->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            $this->jwtService->revokeToken(substr($header, 7));
        }

        return $this->success([], '退出成功');
    }

    /**
     * 获取当前管理员信息
     * GET /admin/api/auth/me
     */
    public function profile(): \think\Response
    {
        $adminId = $this->request->admin['id'] ?? 0;
        $admin = $this->adminModel->find($adminId);

        if (!$admin) {
            return $this->error('管理员不存在', 404);
        }

        return $this->success([
            'id'       => $admin->id,
            'username' => $admin->username,
            'nickname' => $admin->nickname,
            'avatar'   => $admin->avatar,
            'role_id'  => $admin->role_id,
        ]);
    }
}
