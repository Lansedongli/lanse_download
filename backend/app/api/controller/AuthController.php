<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\controller\BaseController;
use app\common\model\User as UserModel;
use app\common\service\JwtService;
use think\App;
use think\facade\Db;
use think\facade\Validate;

/**
 * 用户认证控制器
 * 处理注册、登录、Token刷新、退出登录
 */
class AuthController extends BaseController
{
    private UserModel $userModel;
    private JwtService $jwtService;

    public function __construct(App $app, UserModel $userModel, JwtService $jwtService)
    {
        parent::__construct($app);
        $this->userModel  = $userModel;
        $this->jwtService = $jwtService;
    }

    /**
     * 用户注册
     * POST /api/v1/auth/register
     *
     * @body username string 用户名 (3-50字符)
     * @body password string 密码 (6-32字符)
     * @body email    string 邮箱
     */
    public function register(): \think\Response
    {
        $data = $this->request->post();

        // 验证
        $validate = Validate::rule([
            'username' => 'require|length:3,50|alphaDash|unique:user,username',
            'password' => 'require|length:6,32',
            'email'    => 'require|email|unique:user,email',
        ])->message([
            'username.require'   => '用户名不能为空',
            'username.length'    => '用户名长度需在3-50字符之间',
            'username.alphaDash'  => '用户名只能包含字母、数字、下划线和横杠',
            'username.unique'    => '用户名已被占用',
            'password.require'   => '密码不能为空',
            'password.length'    => '密码长度需在6-32字符之间',
            'email.require'      => '邮箱不能为空',
            'email.email'        => '邮箱格式不正确',
            'email.unique'       => '邮箱已被注册',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        // 创建用户
        $user = $this->userModel->create([
            'username'    => $data['username'],
            'password'    => password_hash($data['password'], PASSWORD_BCRYPT),
            'email'       => $data['email'],
            'group_id'    => 1,  // 默认普通会员组
            'register_ip' => $this->request->ip(),
            'status'      => 1,
        ]);

        // 签发 Token
        $tokens = $this->jwtService->createToken([
            'id'       => $user->id,
            'username' => $user->username,
            'group_id' => $user->group_id,
            'level'    => 1,
        ]);

        return $this->success([
            'user'   => [
                'id'       => $user->id,
                'username' => $user->username,
                'email'    => $user->email,
            ],
            'tokens' => $tokens,
        ], '注册成功', 201);
    }

    /**
     * 用户登录
     * POST /api/v1/auth/login
     *
     * @body username string 用户名
     * @body password string 密码
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

        // 查找用户
        $user = $this->userModel->where('username', $data['username'])->find();
        if (!$user || !password_verify($data['password'], $user->password)) {
            return $this->error('用户名或密码错误', 401);
        }

        if ($user->status !== 1) {
            return $this->error('账号已被禁用', 403);
        }

        // 更新登录信息
        $user->login_ip   = $this->request->ip();
        $user->login_time = date('Y-m-d H:i:s');
        $user->save();

        // 记录登录日志
        try {
            Db::table('user_login_log')->insert([
                'user_id'   => $user->id,
                'username'  => $user->username,
                'ip'        => $this->request->ip(),
                'user_agent'=> mb_substr($this->request->header('User-Agent', ''), 0, 500),
                'status'    => 1,
                'created_at'=> date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) { /* 日志失败不影响主流程 */ }

        // 签发 Token
        $tokens = $this->jwtService->createToken([
            'id'       => $user->id,
            'username' => $user->username,
            'group_id'  => $user->group_id,
            'level'     => 1,
        ]);

        return $this->success([
            'user'   => [
                'id'        => $user->id,
                'username'  => $user->username,
                'email'     => $user->email,
                'nickname'  => $user->nickname,
                'avatar'    => $user->avatar,
                'group_id'  => $user->group_id,
                'points'    => $user->points,
                'expire_time' => $user->expire_time,
            ],
            'tokens' => $tokens,
        ], '登录成功');
    }

    /**
     * 刷新 Token
     * POST /api/v1/auth/refresh
     *
     * @body refresh_token string 刷新令牌
     */
    public function refresh(): \think\Response
    {
        $refreshToken = $this->request->post('refresh_token', '');

        if (empty($refreshToken)) {
            return $this->error('刷新令牌不能为空', 422);
        }

        $tokens = $this->jwtService->refreshToken($refreshToken);

        if (!$tokens) {
            return $this->error('刷新令牌无效或已过期', 401);
        }

        return $this->success($tokens, 'Token刷新成功');
    }

    /**
     * 退出登录
     * POST /api/v1/auth/logout
     */
    public function logout(): \think\Response
    {
        $header = $this->request->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            $this->jwtService->revokeToken(substr($header, 7));
        }

        return $this->success([], '退出成功');
    }
}
