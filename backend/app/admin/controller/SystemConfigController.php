<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\BaseController;
use think\App;
use think\facade\Db;
use think\facade\Validate;

/**
 * 系统配置控制器
 * 管理全站配置项（按分组管理）
 */
class SystemConfigController extends BaseController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /**
     * 配置列表（按分组筛选）
     * GET /admin/api/system-configs?group=base
     */
    public function index(): \think\Response
    {
        $group = $this->request->get('group', '');

        $list = Db::table('system_config')
            ->when($group, fn($q) => $q->where('group', $group))
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select();

        return $this->success($list->toArray());
    }

    /**
     * 获取所有分组
     * GET /admin/api/system-configs/groups
     */
    public function groups(): \think\Response
    {
        $groups = Db::table('system_config')
            ->distinct(true)
            ->column('group');

        return $this->success($groups);
    }

    /**
     * 创建配置项
     * POST /admin/api/system-configs
     */
    public function create(): \think\Response
    {
        $data = $this->request->post();

        $validate = Validate::rule([
            'group' => 'require|alphaDash|max:50',
            'key'   => 'require|alphaDash|max:100|unique:system_config',
            'title' => 'require|max:200',
        ])->message([
            'group.require' => '配置分组不能为空',
            'key.require'   => '配置键不能为空',
            'key.unique'    => '配置键已存在',
            'title.require' => '配置标题不能为空',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        Db::table('system_config')->insert([
            'group'       => $data['group'],
            'key'         => $data['key'],
            'value'       => $data['value'] ?? '',
            'type'        => $data['type'] ?? 'string',
            'title'       => $data['title'],
            'description' => $data['description'] ?? '',
            'sort'        => (int) ($data['sort'] ?? 0),
            'is_system'   => (int) ($data['is_system'] ?? 0),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        return $this->success([], '配置项创建成功');
    }

    /**
     * 更新配置项
     * PUT /admin/api/system-configs/<id>
     */
    public function update(int $id): \think\Response
    {
        $data = $this->request->put();

        $validate = Validate::rule([
            'key' => "alphaDash|max:100|unique:system_config,key,{$id}",
            'title' => 'max:200',
        ])->message(['key.unique' => '配置键已存在']);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        $update = array_intersect_key($data, array_flip([
            'group','key','value','type','title','description','sort','is_system'
        ]));
        $update['updated_at'] = date('Y-m-d H:i:s');
        Db::table('system_config')->where('id', $id)->update($update);

        return $this->success([], '配置项更新成功');
    }

    /**
     * 删除配置项
     * DELETE /admin/api/system-configs/<id>
     */
    public function delete(int $id): \think\Response
    {
        $config = Db::table('system_config')->find($id);
        if ($config && $config['is_system']) {
            return $this->error('系统配置项不允许删除', 403);
        }

        Db::table('system_config')->delete($id);
        return $this->success([], '配置项已删除');
    }

    /**
     * 批量保存配置（按key批量更新value）
     * PUT /admin/api/system-configs/batch
     */
    public function batchUpdate(): \think\Response
    {
        $data = $this->request->put();
        if (empty($data) || !is_array($data)) {
            return $this->error('参数错误', 422);
        }

        foreach ($data as $key => $value) {
            Db::table('system_config')
                ->where('key', $key)
                ->update([
                    'value'      => (string) $value,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }

        return $this->success([], '批量保存成功');
    }
}
