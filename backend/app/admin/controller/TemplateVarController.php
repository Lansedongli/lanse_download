<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\BaseController;
use think\App;
use think\facade\Db;
use think\facade\Validate;

/**
 * 模板变量控制器
 * 管理站点全局模板变量（站点名称、关键词、描述等）
 */
class TemplateVarController extends BaseController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /**
     * 变量列表
     * GET /admin/api/template-vars
     */
    public function index(): \think\Response
    {
        $list = Db::table('template_var')->order('id', 'asc')->select();
        return $this->success($list->toArray());
    }

    /**
     * 创建变量
     * POST /admin/api/template-vars
     */
    public function create(): \think\Response
    {
        $data = $this->request->post();

        $validate = Validate::rule([
            'name'  => 'require|alphaDash|max:50|unique:template_var',
            'value' => 'require',
        ])->message([
            'name.require'   => '变量名不能为空',
            'name.alphaDash' => '变量名只能包含字母数字下划线和横线',
            'name.unique'    => '变量名已存在',
            'value.require'  => '变量值不能为空',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        Db::table('template_var')->insert([
            'name'        => $data['name'],
            'value'       => $data['value'],
            'description' => $data['description'] ?? '',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        return $this->success([], '变量创建成功');
    }

    /**
     * 更新变量
     * PUT /admin/api/template-vars/<id>
     */
    public function update(int $id): \think\Response
    {
        $data = $this->request->put();

        $validate = Validate::rule([
            'name'  => "alphaDash|max:50|unique:template_var,name,{$id}",
        ])->message(['name.unique' => '变量名已存在']);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        $update = array_intersect_key($data, array_flip(['name','value','description']));
        $update['updated_at'] = date('Y-m-d H:i:s');
        Db::table('template_var')->where('id', $id)->update($update);

        return $this->success([], '变量更新成功');
    }

    /**
     * 删除变量
     * DELETE /admin/api/template-vars/<id>
     */
    public function delete(int $id): \think\Response
    {
        Db::table('template_var')->delete($id);
        return $this->success([], '变量已删除');
    }

    /**
     * 批量获取变量值（key-value对）
     * GET /admin/api/template-vars/map
     */
    public function map(): \think\Response
    {
        $rows = Db::table('template_var')->column('value', 'name');
        return $this->success($rows);
    }
}
