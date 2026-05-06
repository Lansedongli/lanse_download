<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\BaseController;
use app\common\enum\SoftwareStatusEnum;
use app\common\model\Software;
use think\App;
use think\facade\Validate;

/**
 * 下载对象（软件）管理
 */
class SoftwareController extends BaseController
{
    private readonly Software $softwareModel;

    public function __construct(App $app, Software $softwareModel)
    {
        parent::__construct($app);
        $this->softwareModel = $softwareModel;
    }

    /**
     * 下载对象列表（支持分类/状态/关键字筛选+分页）
     * GET /admin/api/software?category_id=&status=&keyword=&page=1&limit=20
     */
    public function index(): \think\Response
    {
        $categoryId = $this->request->get('category_id');
        $status     = $this->request->get('status');
        $keyword    = $this->request->get('keyword');
        $page       = max(1, (int) $this->request->get('page', 1));
        $limit      = min(100, max(1, (int) $this->request->get('limit', 20)));

        $query = $this->softwareModel->alias('s')
            ->field('s.*')
            ->with(['category', 'tags'])
            ->order('s.id desc');

        // 分类筛选（含子分类）
        if ($categoryId !== null && $categoryId !== '') {
            $cid = (int) $categoryId;
            if ($cid > 0) {
                $categoryModel = new \app\common\model\Category();
                $childIds = $categoryModel->getChildIds($cid);
                $query->whereIn('s.category_id', $childIds);
            }
        }

        // 状态筛选
        if ($status !== null && $status !== '') {
            $statusInt = (int) $status;
            $validStatuses = array_column(SoftwareStatusEnum::cases(), 'value');
            if (in_array($statusInt, $validStatuses, true)) {
                $query->where('s.status', $statusInt);
            }
        }

        // 关键字搜索
        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('s.name', 'like', "%{$keyword}%")
                  ->whereOr('s.description', 'like', "%{$keyword}%");
            });
        }

        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();

        return $this->success([
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
            'list'  => $list,
        ]);
    }

    /**
     * 下载对象详情（含附件+标签）
     * GET /admin/api/software/:id
     */
    public function detail(int $id): \think\Response
    {
        $software = $this->softwareModel
            ->with(['category', 'attachments', 'tags'])
            ->find($id);

        if (!$software) {
            return $this->error('资源不存在', 404);
        }

        return $this->success($software->toArray());
    }

    /**
     * 新增下载对象
     * POST /admin/api/software
     */
    public function create(): \think\Response
    {
        $data = $this->request->post();

        $validate = Validate::rule([
            'name'         => 'require|max:200',
            'category_id'  => 'require|integer',
            'type_id'      => 'require|integer',
            'description'  => 'max:5000',
            'version'      => 'max:50',
            'points'       => 'integer',
            'status'       => 'in:0,1,2,3',
            'is_recommend' => 'in:0,1',
            'sort'         => 'integer',
            'images'       => 'array',
            'tag_ids'      => 'array',
        ])->message([
            'name.require'        => '资源名称不能为空',
            'name.max'            => '资源名称不能超过200个字符',
            'category_id.require' => '所属分类不能为空',
            'type_id.require'     => '资源类型不能为空',
            'status.in'           => '状态值无效',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        // 验证分类存在
        $category = \app\common\model\Category::find($data['category_id']);
        if (!$category) {
            return $this->error('所选分类不存在', 404);
        }

        // 验证类型存在
        $type = \app\common\model\SoftwareType::find($data['type_id']);
        if (!$type) {
            return $this->error('所选资源类型不存在', 404);
        }

        $insert = [
            'name'          => $data['name'],
            'category_id'   => (int) $data['category_id'],
            'type_id'       => (int) $data['type_id'],
            'description'   => $data['description'] ?? '',
            'version'       => $data['version'] ?? '',
            'points'        => $data['points'] ?? 0,
            'status'        => $data['status'] ?? SoftwareStatusEnum::DRAFT->value,
            'is_recommend'  => $data['is_recommend'] ?? 0,
            'sort'          => $data['sort'] ?? 0,
            'images'        => $data['images'] ?? [],
            'created_at'    => date('Y-m-d H:i:s'),
        ];

        $software = $this->softwareModel->create($insert);

        // 处理标签关联
        if (!empty($data['tag_ids']) && is_array($data['tag_ids'])) {
            $software->tags()->attach($data['tag_ids']);
        }

        return $this->success($software->toArray(), '新增资源成功');
    }

    /**
     * 修改下载对象
     * PUT /admin/api/software/:id
     */
    public function update(int $id): \think\Response
    {
        $software = $this->softwareModel->find($id);
        if (!$software) {
            return $this->error('资源不存在', 404);
        }

        $data = $this->request->put();

        $validate = Validate::rule([
            'name'         => 'max:200',
            'category_id'  => 'integer',
            'type_id'      => 'integer',
            'description'  => 'max:5000',
            'version'      => 'max:50',
            'points'       => 'integer',
            'status'       => 'in:0,1,2,3',
            'is_recommend' => 'in:0,1',
            'sort'         => 'integer',
            'images'       => 'array',
            'tag_ids'      => 'array',
        ])->message([
            'name.max'  => '资源名称不能超过200个字符',
            'status.in' => '状态值无效',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        // 验证分类存在
        if (isset($data['category_id'])) {
            $category = \app\common\model\Category::find($data['category_id']);
            if (!$category) {
                return $this->error('所选分类不存在', 404);
            }
        }

        // 验证类型存在
        if (isset($data['type_id'])) {
            $type = \app\common\model\SoftwareType::find($data['type_id']);
            if (!$type) {
                return $this->error('所选资源类型不存在', 404);
            }
        }

        $allowFields = [
            'name', 'category_id', 'type_id', 'description', 'version',
            'points', 'status', 'is_recommend', 'sort', 'images',
        ];
        foreach ($allowFields as $field) {
            if (isset($data[$field])) {
                $software->{$field} = $data[$field];
            }
        }
        $software->save();

        // 处理标签关联（全量替换）
        if (isset($data['tag_ids']) && is_array($data['tag_ids'])) {
            $software->tags()->detach();
            if (!empty($data['tag_ids'])) {
                $software->tags()->attach($data['tag_ids']);
            }
        }

        return $this->success($software->toArray(), '修改资源成功');
    }

    /**
     * 删除下载对象
     * DELETE /admin/api/software/:id
     */
    public function delete(int $id): \think\Response
    {
        $software = $this->softwareModel->find($id);
        if (!$software) {
            return $this->error('资源不存在', 404);
        }

        // 清除标签关联
        $software->tags()->detach();
        $software->delete();

        return $this->success([], '删除资源成功');
    }

    /**
     * 审核下载对象（通过/退回）
     * PUT /admin/api/software/:id/audit
     */
    public function audit(int $id): \think\Response
    {
        $software = $this->softwareModel->find($id);
        if (!$software) {
            return $this->error('资源不存在', 404);
        }

        $data = $this->request->put();

        $validate = Validate::rule([
            'status' => 'require|in:1,2',
            'remark' => 'max:500',
        ])->message([
            'status.require' => '审核状态不能为空',
            'status.in'      => '审核状态只能为 1(通过) 或 2(退回)',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        $newStatus = (int) $data['status'];
        $software->status = $newStatus;
        $software->audit_remark = $data['remark'] ?? '';
        $software->audit_time = date('Y-m-d H:i:s');
        $software->save();

        $label = match ($newStatus) {
            SoftwareStatusEnum::PUBLISHED->value => '已通过',
            SoftwareStatusEnum::REJECTED->value  => '已退回',
            default                               => '操作完成',
        };

        return $this->success($software->toArray(), "审核{$label}");
    }
}
