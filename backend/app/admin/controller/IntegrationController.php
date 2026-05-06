<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\BaseController;
use app\common\model\IntegrationConfig;
use app\common\service\IntegrationService;
use think\App;
use think\facade\Validate;

/**
 * 万能会员整合 - 后台管理控制器
 * 管理第三方用户系统接口配置
 */
class IntegrationController extends BaseController
{
    private IntegrationConfig $configModel;
    private IntegrationService $integrationService;

    public function __construct(App $app, IntegrationConfig $configModel, IntegrationService $integrationService)
    {
        parent::__construct($app);
        $this->configModel        = $configModel;
        $this->integrationService = $integrationService;
    }

    /**
     * 获取所有配置列表
     * GET /admin/api/integration/configs
     */
    public function configs(): \think\Response
    {
        $configs = $this->integrationService->getConfigs();
        return $this->success($configs);
    }

    /**
     * 新增配置
     * POST /admin/api/integration/config
     */
    public function createConfig(): \think\Response
    {
        $data = $this->request->post();

        $validate = Validate::rule([
            'name'           => 'require|max:100',
            'db_host'        => 'require|max:100',
            'db_port'        => 'integer|>:0',
            'db_name'        => 'require|max:100',
            'db_user'        => 'require|max:100',
            'db_password'    => 'require|max:255',
            'user_table'     => 'require|max:100',
            'username_field' => 'require|max:50',
            'password_field' => 'require|max:50',
            'password_hash'  => 'require|in:md5,sha1,bcrypt,plain',
        ])->message([
            'name.require'            => '接口名称不能为空',
            'db_host.require'         => '数据库主机不能为空',
            'db_name.require'         => '数据库名不能为空',
            'db_user.require'         => '数据库用户不能为空',
            'db_password.require'     => '数据库密码不能为空',
            'user_table.require'      => '用户表名不能为空',
            'username_field.require'  => '用户名字段不能为空',
            'password_field.require'  => '密码字段不能为空',
            'password_hash.require'   => '密码加密方式不能为空',
            'password_hash.in'        => '加密方式仅支持: md5, sha1, bcrypt, plain',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        $config = $this->configModel->create([
            'name'           => $data['name'],
            'db_host'        => $data['db_host'],
            'db_port'        => $data['db_port'] ?? 3306,
            'db_name'        => $data['db_name'],
            'db_user'        => $data['db_user'],
            'db_password'    => $data['db_password'],
            'db_charset'     => $data['db_charset'] ?? 'utf8mb4',
            'user_table'     => $data['user_table'],
            'user_id_field'  => $data['user_id_field'] ?? 'id',
            'username_field' => $data['username_field'],
            'password_field' => $data['password_field'],
            'group_field'    => $data['group_field'] ?? '',
            'points_field'   => $data['points_field'] ?? '',
            'password_hash'  => $data['password_hash'],
            'status'         => $data['status'] ?? 1,
        ]);

        return $this->success($config->toArray(), '配置创建成功', 201);
    }

    /**
     * 更新配置
     * PUT /admin/api/integration/config/:id
     */
    public function updateConfig(int $id): \think\Response
    {
        $config = $this->configModel->find($id);
        if (!$config) {
            return $this->error('配置不存在', 404);
        }

        $data = $this->request->put();

        $validate = Validate::rule([
            'name'           => 'max:100',
            'db_host'        => 'max:100',
            'db_port'        => 'integer|>:0',
            'db_name'        => 'max:100',
            'db_user'        => 'max:100',
            'password_hash'  => 'in:md5,sha1,bcrypt,plain',
        ])->message([
            'password_hash.in' => '加密方式仅支持: md5, sha1, bcrypt, plain',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        // 允许更新的字段
        $allowFields = [
            'name', 'db_host', 'db_port', 'db_name', 'db_user',
            'db_password', 'db_charset', 'user_table', 'user_id_field',
            'username_field', 'password_field', 'group_field', 'points_field',
            'password_hash', 'status',
        ];

        $updateData = [];
        foreach ($allowFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (!empty($updateData)) {
            $config->save($updateData);
        }

        return $this->success($config->toArray(), '配置更新成功');
    }

    /**
     * 删除配置
     * DELETE /admin/api/integration/config/:id
     */
    public function deleteConfig(int $id): \think\Response
    {
        $config = $this->configModel->find($id);
        if (!$config) {
            return $this->error('配置不存在', 404);
        }

        $config->delete();
        return $this->success([], '配置已删除');
    }

    /**
     * 测试连接
     * POST /admin/api/integration/test/:id
     */
    public function testConnection(int $id): \think\Response
    {
        $result = $this->integrationService->testConnection($id);
        if ($result['success']) {
            return $this->success($result, '连接测试成功');
        }
        return $this->error($result['message'], 500, $result);
    }
}
