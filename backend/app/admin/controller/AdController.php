<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\BaseController;
use think\App;
use think\facade\Db;
use think\facade\Validate;

/**
 * 广告管理控制器
 * 广告位 CRUD + 广告内容 CRUD
 */
class AdController extends BaseController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    // ========== 广告位管理 ==========

    /**
     * 广告位列表
     * GET /admin/api/ads/places
     */
    public function places(): \think\Response
    {
        $list = Db::table('ad_place')->order('id', 'asc')->select();
        return $this->success($list->toArray());
    }

    /**
     * 创建广告位
     * POST /admin/api/ads/places
     */
    public function createPlace(): \think\Response
    {
        $data = $this->request->post();
        $validate = Validate::rule([
            'name'  => 'require|max:100',
            'code'  => 'require|alphaDash|max:50|unique:ad_place',
            'width' => 'integer',
            'height'=> 'integer',
        ])->message([
            'name.require'   => '广告位名称不能为空',
            'code.require'   => '广告位标识不能为空',
            'code.alphaDash' => '标识只能包含字母数字下划线和横线',
            'code.unique'    => '广告位标识已存在',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        Db::table('ad_place')->insert([
            'name'        => $data['name'],
            'code'        => $data['code'],
            'width'       => (int) ($data['width'] ?? 0),
            'height'      => (int) ($data['height'] ?? 0),
            'description' => $data['description'] ?? '',
            'status'      => $data['status'] ?? 1,
        ]);

        return $this->success([], '广告位创建成功');
    }

    /**
     * 更新广告位
     * PUT /admin/api/ads/places/<id>
     */
    public function updatePlace(int $id): \think\Response
    {
        $data = $this->request->put();
        $validate = Validate::rule([
            'name'  => 'max:100',
            'code'  => "alphaDash|max:50|unique:ad_place,code,{$id}",
        ])->message(['code.unique' => '广告位标识已存在']);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        $update = array_intersect_key($data, array_flip(['name','code','width','height','description','status']));
        Db::table('ad_place')->where('id', $id)->update($update);

        return $this->success([], '广告位更新成功');
    }

    /**
     * 删除广告位
     * DELETE /admin/api/ads/places/<id>
     */
    public function deletePlace(int $id): \think\Response
    {
        // 清理关联广告
        Db::table('ad_record')->where('place_id', $id)->delete();
        Db::table('ad_place')->delete($id);
        return $this->success([], '广告位已删除');
    }

    // ========== 广告内容管理 ==========

    /**
     * 广告列表（按广告位筛选）
     * GET /admin/api/ads/records?place_id=1
     */
    public function records(): \think\Response
    {
        $placeId = (int) $this->request->get('place_id', 0);

        $list = Db::table('ad_record')
            ->when($placeId > 0, fn($q) => $q->where('place_id', $placeId))
            ->order('sort', 'asc')
            ->order('id', 'desc')
            ->select();

        return $this->success($list->toArray());
    }

    /**
     * 创建广告
     * POST /admin/api/ads/records
     */
    public function createRecord(): \think\Response
    {
        $data = $this->request->post();
        $validate = Validate::rule([
            'place_id' => 'require|integer|>:0',
            'title'    => 'require|max:200',
        ])->message([
            'place_id.require' => '广告位不能为空',
            'title.require'    => '广告标题不能为空',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        $id = Db::table('ad_record')->insertGetId([
            'place_id'   => (int) $data['place_id'],
            'title'      => $data['title'],
            'type'       => $data['type'] ?? 'image',
            'show_type'  => $data['show_type'] ?? 'normal',
            'content'    => $data['content'] ?? '',
            'image_url'  => $data['image_url'] ?? '',
            'link_url'   => $data['link_url'] ?? '',
            'width'      => (int) ($data['width'] ?? 0),
            'height'     => (int) ($data['height'] ?? 0),
            'start_time' => $data['start_time'] ?? null,
            'end_time'   => $data['end_time'] ?? null,
            'sort'       => (int) ($data['sort'] ?? 0),
            'status'     => $data['status'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->success(['id' => $id], '广告创建成功');
    }

    /**
     * 更新广告
     * PUT /admin/api/ads/records/<id>
     */
    public function updateRecord(int $id): \think\Response
    {
        $data = $this->request->put();
        $update = array_intersect_key($data, array_flip([
            'title','type','show_type','content','image_url','link_url',
            'width','height','start_time','end_time','sort','status'
        ]));
        $update['updated_at'] = date('Y-m-d H:i:s');

        Db::table('ad_record')->where('id', $id)->update($update);
        return $this->success([], '广告更新成功');
    }

    /**
     * 删除广告
     * DELETE /admin/api/ads/records/<id>
     */
    public function deleteRecord(int $id): \think\Response
    {
        Db::table('ad_click_log')->where('ad_id', $id)->delete();
        Db::table('ad_record')->delete($id);
        return $this->success([], '广告已删除');
    }

    /**
     * 广告点击统计
     * GET /admin/api/ads/stats?ad_id=1
     */
    public function stats(): \think\Response
    {
        $adId = (int) $this->request->get('ad_id', 0);

        // 简单统计
        $data = Db::table('ad_click_log')
            ->when($adId > 0, fn($q) => $q->where('ad_id', $adId))
            ->field([
                'ad_id',
                'COUNT(*) as total_clicks',
                'DATE(created_at) as date'
            ])
            ->group('ad_id, date')
            ->order('date', 'desc')
            ->limit(30)
            ->select();

        return $this->success($data->toArray());
    }
}
