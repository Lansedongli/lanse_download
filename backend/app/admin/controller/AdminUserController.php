<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\BaseController;
use app\common\model\AdminUser as AdminUserModel;
use app\common\model\Role as RoleModel;
use think\App;
use think\facade\Validate;

/**
 * 管理员管理控制器 (RBAC)
 * 只有超级管理员可以管理其他管理员
 */
class AdminUserController extends BaseController
{
    private AdminUserModel $adminModel;
    private RoleModel $roleModel;

    public function __construct(App $app, AdminUserModel $adminModel, RoleModel $roleModel)
    {
        parent::__construct($app);
        $this->adminModel = $adminModel;
        $this->roleModel = $roleModel;
    }

    /**
     * 管理员列表
     * GET /admin/api/admin-users
     */
    public function index(): \think\Response
    {
        $admins = $this->adminModel
            ->field('id,username,nickname,email,mobile,role_id,status,login_ip,login_time,login_count,created_at')
            ->order('id', 'asc')
            ->select();

        // 关联角色名称
        $roleIds = array_column($admins->toArray(), 'role_id');
        if (!empty($roleIds)) {
            $roles = $this->roleModel->whereIn('id', $roleIds)->column('name', 'id');
            foreach ($admins as $admin) {
                $admin->role_name = $roles[$admin->role_id] ?? '-';
            }
        }

        return $this->success($admins->toArray());
    }

    /**
     * 管理员详情
     * GET /admin/api/admin-users/<id>
     */
    public function detail(int $id): \think\Response
    {
        $admin = $this->adminModel
            ->field('id,username,nickname,avatar,email,mobile,role_id,status,login_ip,login_time,login_count,created_at')
            ->find($id);

        if (!$admin) {
            return $this->error('管理员不存在', 404);
        }

        $role = $this->roleModel->find($admin->role_id);
        $admin->role_name = $role ? $role->name : '-';

        return $this->success($admin->toArray());
    }

    /**
     * 创建管理员
     * POST /admin/api/admin-users
     */
    public function create(): \think\Response
    {
        $data = $this->request->post();

        $validate = Validate::rule([
            'username' => 'require|alphaDash|min:3|max:50|unique:admin_user',
            'password' => 'require|min:6|max:20',
            'nickname' => 'max:100',
            'role_id'  => 'require|number',
        ])->message([
            'username.require'   => '用户名不能为空',
            'username.alphaDash' => '用户名只能包含字母数字下划线和横线',
            'username.min'       => '用户名至少3个字符',
            'username.max'       => '用户名不能超过50个字符',
            'username.unique'    => '用户名已存在',
            'password.require'   => '密码不能为空',
            'password.min'       => '密码至少6位',
            'password.max'       => '密码不能超过20位',
            'role_id.require'    => '角色不能为空',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        // 校验角色是否存在
        $role = $this->roleModel->find($data['role_id']);
        if (!$role) {
            return $this->error('所选角色不存在', 422);
        }

        $admin = new AdminUserModel();
        $admin->username = $data['username'];
        $admin->password = password_hash($data['password'], PASSWORD_BCRYPT);
        $admin->nickname = $data['nickname'] ?? $data['username'];
        $admin->email    = $data['email'] ?? '';
        $admin->mobile   = $data['mobile'] ?? '';
        $admin->role_id  = (int) $data['role_id'];
        $admin->status   = $data['status'] ?? 1;
        $admin->save();

        return $this->success([
            'id'       => $admin->id,
            'username' => $admin->username,
            'nickname' => $admin->nickname,
            'role_id'  => $admin->role_id,
        ], '管理员创建成功');
    }

    /**
     * 更新管理员
     * PUT /admin/api/admin-users/<id>
     */
    public function update(int $id): \think\Response
    {
        $admin = $this->adminModel->find($id);
        if (!$admin) {
            return $this->error('管理员不存在', 404);
        }

        $data = $this->request->put();

        $validate = Validate::rule([
            'username' => "alphaDash|min:3|max:50|unique:admin_user,username,{$id}",
            'password' => 'min:6|max:20',
            'nickname' => 'max:100',
        ])->message([
            'username.alphaDash' => '用户名只能包含字母数字下划线和横线',
            'username.unique'    => '用户名已存在',
            'password.min'       => '密码至少6位',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        if (isset($data['username'])) {
            $admin->username = $data['username'];
        }
        if (!empty($data['password'])) {
            $admin->password = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        if (isset($data['nickname'])) {
            $admin->nickname = $data['nickname'];
        }
        if (isset($data['email'])) {
            $admin->email = $data['email'];
        }
        if (isset($data['mobile'])) {
            $admin->mobile = $data['mobile'];
        }
        if (isset($data['role_id'])) {
            $role = $this->roleModel->find($data['role_id']);
            if (!$role) {
                return $this->error('所选角色不存在', 422);
            }
            $admin->role_id = (int) $data['role_id'];
        }
        if (isset($data['status'])) {
            $admin->status = $data['status'];
        }
        $admin->save();

        return $this->success([
            'id'       => $admin->id,
            'username' => $admin->username,
            'nickname' => $admin->nickname,
            'role_id'  => $admin->role_id,
        ], '管理员更新成功');
    }

    /**
     * 删除管理员
     * DELETE /admin/api/admin-users/<id>
     */
    public function delete(int $id): \think\Response
    {
        // 不能删除自己
        $currentId = $this->request->admin['id'] ?? 0;
        if ($id == $currentId) {
            return $this->error('不能删除自己的账号', 403);
        }

        $admin = $this->adminModel->find($id);
        if (!$admin) {
            return $this->error('管理员不存在', 404);
        }

        $admin->delete();
        return $this->success([], '管理员删除成功');
    }

    /**
     * 获取可分配的角色列表
     * GET /admin/api/admin-users/roles
     */
    public function roles(): \think\Response
    {
        $roles = $this->roleModel
            ->where('status', 1)
            ->field('id,name,code')
            ->order('sort', 'asc')
            ->select();

        return $this->success($roles->toArray());
    }
}
