<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\BaseController;
use app\common\model\Role as RoleModel;
use think\App;
use think\facade\Validate;

/**
 * 角色管理控制器 (RBAC)
 * 管理后台角色的增删改查及权限分配
 */
class RoleController extends BaseController
{
    private RoleModel $roleModel;

    public function __construct(App $app, RoleModel $roleModel)
    {
        parent::__construct($app);
        $this->roleModel = $roleModel;
    }

    /**
     * 角色列表
     * GET /admin/api/roles
     */
    public function index(): \think\Response
    {
        $roles = $this->roleModel->order('sort', 'asc')->select();
        return $this->success($roles->toArray());
    }

    /**
     * 角色详情
     * GET /admin/api/roles/<id>
     */
    public function detail(int $id): \think\Response
    {
        $role = $this->roleModel->find($id);
        if (!$role) {
            return $this->error('角色不存在', 404);
        }
        return $this->success($role->toArray());
    }

    /**
     * 创建角色
     * POST /admin/api/roles
     */
    public function create(): \think\Response
    {
        $data = $this->request->post();

        $validate = Validate::rule([
            'name' => 'require|max:50',
            'code' => 'require|alphaDash|max:50|unique:role',
        ])->message([
            'name.require'   => '角色名称不能为空',
            'name.max'       => '角色名称不能超过50个字符',
            'code.require'   => '角色标识不能为空',
            'code.alphaDash' => '角色标识只能包含字母数字下划线和横线',
            'code.unique'    => '角色标识已存在',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        $role = new RoleModel();
        $role->name        = $data['name'];
        $role->code        = $data['code'];
        $role->description = $data['description'] ?? '';
        $role->permissions = $data['permissions'] ?? [];
        $role->status      = $data['status'] ?? 1;
        $role->sort        = $data['sort'] ?? 0;
        $role->save();

        return $this->success($role->toArray(), '角色创建成功');
    }

    /**
     * 更新角色
     * PUT /admin/api/roles/<id>
     */
    public function update(int $id): \think\Response
    {
        $role = $this->roleModel->find($id);
        if (!$role) {
            return $this->error('角色不存在', 404);
        }

        // 超级管理员不允许修改
        if ($role->code === 'super_admin') {
            return $this->error('超级管理员角色不允许修改', 403);
        }

        $data = $this->request->put();

        $validate = Validate::rule([
            'name' => 'max:50',
            'code' => "alphaDash|max:50|unique:role,code,{$id}",
        ])->message([
            'code.alphaDash' => '角色标识只能包含字母数字下划线和横线',
            'code.unique'    => '角色标识已存在',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        if (isset($data['name'])) {
            $role->name = $data['name'];
        }
        if (isset($data['code'])) {
            $role->code = $data['code'];
        }
        if (isset($data['description'])) {
            $role->description = $data['description'];
        }
        if (isset($data['permissions'])) {
            $role->permissions = $data['permissions'];
        }
        if (isset($data['status'])) {
            $role->status = $data['status'];
        }
        if (isset($data['sort'])) {
            $role->sort = $data['sort'];
        }
        $role->save();

        return $this->success($role->toArray(), '角色更新成功');
    }

    /**
     * 删除角色
     * DELETE /admin/api/roles/<id>
     */
    public function delete(int $id): \think\Response
    {
        $role = $this->roleModel->find($id);
        if (!$role) {
            return $this->error('角色不存在', 404);
        }

        // 禁止删除超级管理员
        if ($role->code === 'super_admin') {
            return $this->error('超级管理员角色不允许删除', 403);
        }

        // 检查是否有管理员使用这个角色
        $count = \app\common\model\AdminUser::where('role_id', $id)->count();
        if ($count > 0) {
            return $this->error("该角色下有 {$count} 个管理员，请先解除关联", 422);
        }

        $role->delete();
        return $this->success([], '角色删除成功');
    }

    /**
     * 获取所有可用权限列表
     * GET /admin/api/roles/permissions
     */
    public function permissions(): \think\Response
    {
        $permissions = [
            [
                'module' => '角色管理',
                'code'   => 'role',
                'actions' => ['list', 'create', 'update', 'delete'],
            ],
            [
                'module' => '管理员管理',
                'code'   => 'admin_user',
                'actions' => ['list', 'create', 'update', 'delete'],
            ],
            [
                'module' => '用户管理',
                'code'   => 'user',
                'actions' => ['list', 'update', 'points', 'group', 'ban'],
            ],
            [
                'module' => '软件管理',
                'code'   => 'software',
                'actions' => ['list', 'create', 'update', 'delete', 'audit'],
            ],
            [
                'module' => '分类管理',
                'code'   => 'category',
                'actions' => ['list', 'create', 'update', 'delete'],
            ],
            [
                'module' => '支付管理',
                'code'   => 'pay',
                'actions' => ['list', 'create', 'update', 'delete'],
            ],
            [
                'module' => '充值管理',
                'code'   => 'recharge',
                'actions' => ['list', 'create'],
            ],
            [
                'module' => '报表统计',
                'code'   => 'report',
                'actions' => ['list'],
            ],
            [
                'module' => '广告管理',
                'code'   => 'ad',
                'actions' => ['list', 'create', 'update', 'delete'],
            ],
            [
                'module' => '广告管理',
                'code'   => 'ad',
                'actions' => ['list', 'create', 'update', 'delete'],
            ],
            [
                'module' => '系统配置',
                'code'   => 'system',
                'actions' => ['list', 'update'],
            ],
            [
                'module' => '数据库管理',
                'code'   => 'db',
                'actions' => ['list', 'backup', 'restore', 'optimize', 'repair'],
            ],
            [
                'module' => '系统配置',
                'code'   => 'system',
                'actions' => ['list', 'update'],
            ],
            [
                'module' => '万能接口',
                'code'   => 'integration',
                'actions' => ['list', 'create', 'update', 'delete'],
            ],
            [
                'module' => '静态化管理',
                'code'   => 'static',
                'actions' => ['generate'],
            ],
        ];

        return $this->success($permissions);
    }
}
