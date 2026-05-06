<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\BaseController;
use app\common\enum\StatusEnum;
use app\common\model\User as UserModel;
use app\common\service\MemberService;
use think\App;
use think\facade\Validate;

/**
 * 会员管理（管理员端）
 */
class UserController extends BaseController
{
    private readonly UserModel $userModel;
    private readonly MemberService $memberService;

    public function __construct(App $app, UserModel $userModel, MemberService $memberService)
    {
        parent::__construct($app);
        $this->userModel     = $userModel;
        $this->memberService = $memberService;
    }

    /**
     * 用户列表（支持会员组/状态/关键字筛选）
     * GET /admin/api/user?group_id=&status=&keyword=&page=1&limit=20
     */
    public function index(): \think\Response
    {
        $groupId  = $this->request->get('group_id');
        $status   = $this->request->get('status');
        $keyword  = $this->request->get('keyword');
        $page     = max(1, (int) $this->request->get('page', 1));
        $limit    = min(100, max(1, (int) $this->request->get('limit', 20)));

        $query = $this->userModel
            ->field('id,username,email,mobile,nickname,avatar,group_id,points,expire_time,status,total_download,today_download,login_time,login_ip,created_at')
            ->order('id desc');

        // 会员组筛选
        if ($groupId !== null && $groupId !== '') {
            $query->where('group_id', (int) $groupId);
        }

        // 状态筛选
        if ($status !== null && $status !== '') {
            $statusInt = (int) $status;
            if (in_array($statusInt, [StatusEnum::DISABLED->value, StatusEnum::ENABLED->value], true)) {
                $query->where('status', $statusInt);
            }
        }

        // 关键字搜索（用户名/邮箱/手机号）
        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('username', 'like', "%{$keyword}%")
                  ->whereOr('email', 'like', "%{$keyword}%")
                  ->whereOr('mobile', 'like', "%{$keyword}%")
                  ->whereOr('nickname', 'like', "%{$keyword}%");
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
     * 用户详情
     * GET /admin/api/user/:id
     */
    public function detail(int $id): \think\Response
    {
        $user = $this->userModel
            ->field('id,username,email,mobile,nickname,avatar,group_id,points,expire_time,status,total_download,today_download,login_time,login_ip,created_at')
            ->find($id);

        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        return $this->success($user->toArray());
    }

    /**
     * 手动调整点数（可为正数或负数）
     * PUT /admin/api/user/:id/adjust-points
     */
    public function adjustPoints(int $id): \think\Response
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $data = $this->request->put();

        $validate = Validate::rule([
            'amount' => 'require|integer|notIn:0',
            'remark' => 'max:500',
        ])->message([
            'amount.require' => '调整金额不能为空',
            'amount.integer' => '调整金额必须为整数',
            'amount.notIn'   => '调整金额不能为0',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        $amount = (int) $data['amount'];
        $remark = $data['remark'] ?? '管理员手动调整';

        if ($amount > 0) {
            $result = $this->memberService->addPoints(
                userId: $id,
                amount: $amount,
                type: 'admin_adjust',
                relationId: 0,
                remark: $remark
            );
        } else {
            $result = $this->memberService->deductPoints(
                userId: $id,
                amount: abs($amount),
                type: 'admin_adjust',
                relationId: 0,
                remark: $remark
            );
        }

        if (!$result) {
            return $this->error('点数调整失败，请检查余额是否充足');
        }

        // 获取最新点数
        $newPoints = $this->userModel->where('id', $id)->value('points');

        return $this->success(
            data: ['points' => $newPoints, 'adjusted' => $amount],
            message: '点数调整成功'
        );
    }

    /**
     * 切换会员组
     * PUT /admin/api/user/:id/change-group
     */
    public function changeGroup(int $id): \think\Response
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $data = $this->request->put();

        $validate = Validate::rule([
            'group_id' => 'require|integer',
            'days'     => 'integer',
        ])->message([
            'group_id.require' => '会员组ID不能为空',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        $groupId = (int) $data['group_id'];
        $days    = (int) ($data['days'] ?? 0);

        $result = $this->memberService->changeGroup(
            userId: $id,
            groupId: $groupId,
            days: $days
        );

        if (!$result) {
            return $this->error('会员组切换失败，请检查会员组是否有效');
        }

        return $this->success([], '会员组切换成功');
    }

    /**
     * 禁用/启用用户
     * PUT /admin/api/user/:id/ban
     */
    public function ban(int $id): \think\Response
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $data = $this->request->put();

        $validate = Validate::rule([
            'status' => 'require|in:0,1',
        ])->message([
            'status.require' => '状态不能为空',
            'status.in'      => '状态值只能为 0(禁用) 或 1(启用)',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        $newStatus = (int) $data['status'];
        $this->userModel->where('id', $id)->update(['status' => $newStatus]);

        $label = match ($newStatus) {
            StatusEnum::ENABLED->value  => '已启用',
            StatusEnum::DISABLED->value => '已禁用',
            default                      => '操作完成',
        };

        return $this->success([], "用户{$label}");
    }
}
