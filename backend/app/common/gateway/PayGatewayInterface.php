<?php
declare(strict_types=1);

namespace app\common\gateway;

/**
 * 支付网关统一接口
 * 所有支付渠道必须实现此接口
 */
interface PayGatewayInterface
{
    /**
     * 创建支付订单并返回支付跳转参数
     *
     * @param array $order 订单信息 [order_no, amount, subject, body, user_id]
     * @return array [success, data|url|message]
     */
    public function pay(array $order): array;

    /**
     * 异步回调验证并处理
     *
     * @param array $params 回调参数（$_POST 或 php://input 原始数据）
     * @return array [success, order_no, trade_no, amount, message]
     */
    public function notify(array $params): array;

    /**
     * 同步跳转验签
     *
     * @param array $params 跳转参数（$_GET）
     * @return array [success, order_no, message]
     */
    public function verifyReturn(array $params): array;

    /**
     * 查询支付结果
     *
     * @param string $orderNo 商户订单号
     * @return array [status: 0待支付/1已支付/2已关闭, trade_no, amount]
     */
    public function query(string $orderNo): array;

    /**
     * 退款
     *
     * @param string $orderNo 商户订单号
     * @param float $amount 退款金额
     * @param string $refundNo 退款单号
     * @return array [success, refund_no, message]
     */
    public function refund(string $orderNo, float $amount, string $refundNo): array;

    /**
     * 获取渠道代码
     */
    public function getChannel(): string;
}
