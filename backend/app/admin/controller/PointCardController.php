<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\BaseController;
use app\common\model\PointCard;
use app\common\model\PointCardBatch;
use app\common\service\PaymentService;
use think\App;
use think\facade\Validate;

/**
 * 点卡管理（管理员端）
 */
class PointCardController extends BaseController
{
    private readonly PointCard $pointCardModel;
    private readonly PointCardBatch $pointCardBatchModel;
    private readonly PaymentService $paymentService;

    public function __construct(
        App $app,
        PointCard $pointCardModel,
        PointCardBatch $pointCardBatchModel,
        PaymentService $paymentService,
    ) {
        parent::__construct($app);
        $this->pointCardModel      = $pointCardModel;
        $this->pointCardBatchModel = $pointCardBatchModel;
        $this->paymentService      = $paymentService;
    }

    /**
     * 批次列表
     * GET /admin/api/point-card/batches?page=1&limit=20
     */
    public function batchList(): \think\Response
    {
        $page  = max(1, (int) $this->request->get('page', 1));
        $limit = min(100, max(1, (int) $this->request->get('limit', 20)));

        $query = $this->pointCardBatchModel->order('id desc');

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
     * 创建批次
     * POST /admin/api/point-card/batches
     */
    public function createBatch(): \think\Response
    {
        $data = $this->request->post();

        $validate = Validate::rule([
            'name'        => 'require|max:100',
            'total_count' => 'integer',
            'batch_no'    => 'max:20',
            'remark'      => 'max:500',
        ])->message([
            'name.require' => '批次名称不能为空',
            'name.max'     => '批次名称不能超过100个字符',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        $batch = $this->pointCardBatchModel->create([
            'name'        => $data['name'],
            'batch_no'    => $data['batch_no'] ?? strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 12)),
            'total_count' => $data['total_count'] ?? 0,
            'used_count'  => 0,
            'remark'      => $data['remark'] ?? '',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        return $this->success($batch->toArray(), '批次创建成功');
    }

    /**
     * 卡密列表（按批次）
     * GET /admin/api/point-card/cards?batch_id=&page=1&limit=20&status=
     */
    public function cardList(): \think\Response
    {
        $batchId = $this->request->get('batch_id');
        $status  = $this->request->get('status');
        $page    = max(1, (int) $this->request->get('page', 1));
        $limit   = min(100, max(1, (int) $this->request->get('limit', 20)));

        $query = $this->pointCardModel->order('id desc');

        // 按批次筛选
        if ($batchId !== null && $batchId !== '') {
            $query->where('batch_id', (int) $batchId);
        }

        // 按状态筛选：0=未使用, 1=已使用, 2=已过期
        if ($status !== null && $status !== '') {
            $statusInt = (int) $status;
            if (in_array($statusInt, [0, 1, 2], true)) {
                $query->where('status', $statusInt);
            }
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
     * 批量生成卡密
     * POST /admin/api/point-card/generate
     */
    public function generateCards(): \think\Response
    {
        $data = $this->request->post();

        $validate = Validate::rule([
            'batch_id'    => 'require|integer',
            'count'       => 'require|integer|between:1,10000',
            'points'      => 'require|integer|gt:0',
            'expire_days' => 'require|integer|gt:0',
            'group_days'  => 'integer',
        ])->message([
            'batch_id.require'    => '批次ID不能为空',
            'count.require'       => '生成数量不能为空',
            'count.between'       => '生成数量需在1-10000之间',
            'points.require'      => '点数不能为空',
            'points.gt'           => '点数必须大于0',
            'expire_days.require' => '有效天数不能为空',
            'expire_days.gt'      => '有效天数必须大于0',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        try {
            $cards = $this->paymentService->generateCards(
                batchId: (int) $data['batch_id'],
                count: (int) $data['count'],
                points: (int) $data['points'],
                expireDays: (int) $data['expire_days'],
                groupDays: (int) ($data['group_days'] ?? 0),
            );
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->error('生成卡密失败: ' . $e->getMessage());
        }

        return $this->success(
            data: ['generated' => count($cards), 'cards' => $cards],
            message: "成功生成 {$data['count']} 张点卡"
        );
    }

    /**
     * 导出卡密（CSV）
     * GET /admin/api/point-card/export?batch_id=
     */
    public function exportCards(): \think\Response
    {
        $batchId = (int) $this->request->get('batch_id', 0);
        if ($batchId <= 0) {
            // 未指定批次时返回所有未使用的卡密
            $cards = $this->pointCardModel
                ->where('status', 0)
                ->order('id asc')
                ->select()
                ->toArray();
        } else {
            $cards = $this->pointCardModel
                ->where('batch_id', $batchId)
                ->order('id asc')
                ->select()
                ->toArray();
        }

        // 构建CSV内容
        $csvData = "卡号,卡密,点数,有效期,状态\n";
        $statusMap = ['未使用', '已使用', '已过期'];
        foreach ($cards as $card) {
            $csvData .= implode(',', [
                $card['card_no'],
                $card['password'],
                $card['points'],
                $card['expire_time'] ?? '不限',
                $statusMap[$card['status']] ?? '未知',
            ]) . "\n";
        }

        return response($csvData, 200, [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="point_cards_' . date('YmdHis') . '.csv"',
        ]);
    }
}
