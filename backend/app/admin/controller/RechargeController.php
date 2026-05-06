<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\BaseController;
use app\common\enum\RechargeTypeEnum;
use app\common\enum\StatusEnum;
use app\common\model\User as UserModel;
use app\common\model\UserRecharge;
use app\common\service\MemberService;
use think\App;
use think\facade\Validate;

/**
 * 充值管理（管理员端）
 */
class RechargeController extends BaseController
{
    private readonly UserRecharge $rechargeModel;
    private readonly UserModel $userModel;
    private readonly MemberService $memberService;

    public function __construct(
        App $app,
        UserRecharge $rechargeModel,
        UserModel $userModel,
        MemberService $memberService,
    ) {
        parent::__construct($app);
        $this->rechargeModel = $rechargeModel;
        $this->userModel     = $userModel;
        $this->memberService = $memberService;
    }

    /**
     * 充值记录列表
     * GET /admin/api/recharge?user_id=&type=&status=&keyword=&page=1&limit=20
     */
    public function index(): \think\Response
    {
        $userId  = $this->request->get('user_id');
        $type    = $this->request->get('type');
        $status  = $this->request->get('status');
        $keyword = $this->request->get('keyword');
        $page    = max(1, (int) $this->request->get('page', 1));
        $limit   = min(100, max(1, (int) $this->request->get('limit', 20)));

        $query = $this->rechargeModel->order('id desc');

        // 用户筛选
        if ($userId !== null && $userId !== '') {
            $query->where('user_id', (int) $userId);
        }

        // 充值类型筛选
        if (!empty($type)) {
            $validTypes = array_column(RechargeTypeEnum::cases(), 'value');
            if (in_array($type, $validTypes, true)) {
                $query->where('type', $type);
            }
        }

        // 状态筛选：0=待支付, 1=已支付, 2=已取消
        if ($status !== null && $status !== '') {
            $statusInt = (int) $status;
            if (in_array($statusInt, [0, 1, 2], true)) {
                $query->where('status', $statusInt);
            }
        }

        // 关键字搜索（订单号）
        if (!empty($keyword)) {
            $query->where('order_no|remark', 'like', "%{$keyword}%");
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
     * 管理员手动充值
     * POST /admin/api/recharge/manual
     */
    public function manualRecharge(): \think\Response
    {
        $data = $this->request->post();

        $validate = Validate::rule([
            'user_id'     => 'require|integer',
            'points'      => 'require|integer|gt:0',
            'group_days'  => 'integer',
            'group_id'    => 'integer',
            'remark'      => 'max:500',
        ])->message([
            'user_id.require' => '用户ID不能为空',
            'points.require'  => '充值点数不能为空',
            'points.gt'       => '充值点数必须大于0',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        $userId    = (int) $data['user_id'];
        $points    = (int) $data['points'];
        $groupDays = (int) ($data['group_days'] ?? 0);
        $groupId   = (int) ($data['group_id'] ?? 0);
        $remark    = $data['remark'] ?? '管理员手动充值';

        // 验证用户是否存在
        $user = $this->userModel->find($userId);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        // 增加点数
        $result = $this->memberService->addPoints(
            userId: $userId,
            amount: $points,
            type: 'admin_manual',
            relationId: 0,
            remark: $remark
        );

        if (!$result) {
            return $this->error('点数充值失败');
        }

        // 赠送会员天数
        if ($groupDays > 0 && $groupId > 0) {
            $this->memberService->changeGroup(
                userId: $userId,
                groupId: $groupId,
                days: $groupDays
            );
        }

        // 创建充值记录
        $orderNo = generate_order_no('AD');
        $record = $this->rechargeModel->create([
            'user_id'    => $userId,
            'order_no'   => $orderNo,
            'type'       => RechargeTypeEnum::ADMIN->value,
            'amount'     => 0,
            'points'     => $points,
            'group_days' => $groupDays,
            'group_id'   => $groupId,
            'status'     => 1,
            'paid_at'    => date('Y-m-d H:i:s'),
            'remark'     => $remark,
            'ip'         => $this->request->ip(),
        ]);

        return $this->success(
            data: [
                'order_no' => $orderNo,
                'points'   => $points,
                'user_id'  => $userId,
            ],
            message: '手动充值成功'
        );
    }
}
