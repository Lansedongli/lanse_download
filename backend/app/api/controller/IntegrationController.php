<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\controller\BaseController;
use app\common\service\IntegrationService;
use think\App;
use think\facade\Validate;

/**
 * 万能会员整合 - 前台API控制器
 * 处理第三方用户系统的登录认证
 */
class IntegrationController extends BaseController
{
    private IntegrationService $integrationService;

    public function __construct(App $app, IntegrationService $integrationService)
    {
        parent::__construct($app);
        $this->integrationService = $integrationService;
    }

    /**
     * 整合登录
     * POST /api/v1/integration/login
     *
     * @body config_id int    接口配置ID
     * @body username  string 用户名
     * @body password  string 密码
     */
    public function login(): \think\Response
    {
        $data = $this->request->post();

        // 参数验证
        $validate = Validate::rule([
            'config_id' => 'require|integer|>:0',
            'username'  => 'require',
            'password'  => 'require',
        ])->message([
            'config_id.require' => '接口配置ID不能为空',
            'config_id.integer' => '接口配置ID需为整数',
            'config_id.>'       => '接口配置ID需大于0',
            'username.require'  => '用户名不能为空',
            'password.require'  => '密码不能为空',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        try {
            // 调用整合服务完成登录
            $result = $this->integrationService->login(
                $data['username'],
                $data['password'],
                (int) $data['config_id']
            );

            return $this->success($result, '登录成功');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 401);
        } catch (\Exception $e) {
            return $this->error('整合登录失败: ' . $e->getMessage(), 500);
        }
    }
}
