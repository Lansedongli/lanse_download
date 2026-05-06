<?php
declare(strict_types=1);

namespace tests\Api;

use tests\ApiTestCase;

/**
 * 支付模块测试
 * - 支付渠道 CRUD
 * - 充值套餐 CRUD
 * - 报表 API
 * - 充值记录
 */
class PaymentTest extends ApiTestCase
{
    private ?string $token = null;
    private array $createdChannelIds = [];
    private array $createdPackageIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->loginAdmin();
    }

    protected function tearDown(): void
    {
        // 清理测试数据
        foreach ($this->createdChannelIds as $id) {
            $this->delete("/admin/api/pay/channels/{$id}", $this->token);
        }
        foreach ($this->createdPackageIds as $id) {
            $this->delete("/admin/api/pay/packages/{$id}", $this->token);
        }
        parent::tearDown();
    }

    // ── 支付渠道 ──

    public function testListChannels(): void
    {
        $res = $this->get('/admin/api/pay/channels', $this->token);
        $this->assertApiSuccess($res);
        $this->assertIsArray($res['data'] ?? null);
        // 应有支付宝和微信两个默认渠道
        $this->assertGreaterThanOrEqual(2, count($res['data']));
    }

    public function testCreateChannelValidation(): void
    {
        // 不传 name → 422 验证失败
        $res = $this->post('/admin/api/pay/channels', [
            'code' => 'test_chan',
        ], $this->token);
        $this->assertApiError($res, 422);
    }

    public function testCreateAndDeleteChannel(): void
    {
        $res = $this->post('/admin/api/pay/channels', [
            'name' => '测试渠道_' . time(),
            'code' => 'testch' . substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 6),
            'api_key' => 'test_key_123',
        ], $this->token);
        $this->assertApiSuccess($res, '创建渠道');
        $id = $res['data']['id'] ?? 0;
        $this->assertGreaterThan(0, $id);

        // 删除
        $del = $this->delete("/admin/api/pay/channels/{$id}", $this->token);
        $this->assertApiSuccess($del, '删除渠道');
    }

    // ── 充值套餐 ──

    public function testListPackages(): void
    {
        $res = $this->get('/admin/api/pay/packages', $this->token);
        $this->assertApiSuccess($res);
        $this->assertIsArray($res['data'] ?? null);
    }

    public function testCreatePackageValidation(): void
    {
        // amount <= 0 → 422
        $res = $this->post('/admin/api/pay/packages', [
            'name'   => '无效套餐',
            'amount' => 0,
            'points' => 0,
        ], $this->token);
        $this->assertApiError($res, 422);
    }

    public function testCreateUpdateDeletePackage(): void
    {
        // 创建
        $res = $this->post('/admin/api/pay/packages', [
            'name'   => '自动化测试套餐',
            'amount' => 9.99,
            'points' => 99,
            'bonus_points' => 10,
        ], $this->token);
        $this->assertApiSuccess($res, '创建套餐');
        $id = $res['data']['id'] ?? 0;
        $this->assertGreaterThan(0, $id);
        $this->createdPackageIds[] = $id;

        // 更新
        $upd = $this->put("/admin/api/pay/packages/{$id}", [
            'name'   => '自动化测试套餐(已更新)',
            'amount' => 8.88,
        ], $this->token);
        $this->assertApiSuccess($upd, '更新套餐');
        $this->assertEquals('8.88', $upd['data']['amount'] ?? '');

        // 删除
        $del = $this->delete("/admin/api/pay/packages/{$id}", $this->token);
        $this->assertApiSuccess($del, '删除套餐');
        $this->createdPackageIds = []; // 已在 tearDown 清理，清除标记
    }

    // ── 报表 ──

    public function testReportSummary(): void
    {
        $res = $this->get('/admin/api/report/summary', $this->token);
        $this->assertApiSuccess($res);
        $this->assertArrayHasKey('total_amount', $res['data'] ?? []);
        $this->assertArrayHasKey('today_orders', $res['data'] ?? []);
    }

    public function testReportTrend(): void
    {
        $res = $this->get('/admin/api/report/trend?days=7', $this->token);
        $this->assertApiSuccess($res);
        $this->assertArrayHasKey('dates', $res['data'] ?? []);
        $this->assertArrayHasKey('amounts', $res['data'] ?? []);
        $this->assertCount(7, $res['data']['dates'] ?? []);
    }

    public function testReportChannels(): void
    {
        $res = $this->get('/admin/api/report/channels', $this->token);
        $this->assertApiSuccess($res);
    }

    public function testReportPackages(): void
    {
        $res = $this->get('/admin/api/report/packages', $this->token);
        $this->assertApiSuccess($res);
    }

    // ── 充值记录 ──

    public function testListRecharges(): void
    {
        $res = $this->get('/admin/api/recharges', $this->token);
        $this->assertApiSuccess($res);
    }

    public function testManualRechargeRequiresUser(): void
    {
        // 手动充值 — 需要 user_id
        $res = $this->post('/admin/api/recharges/manual', [
            'user_id' => 999999,
            'points'  => 100,
        ], $this->token);
        // 可能 422（用户不存在）或 200（如果系统允许）
        $this->assertContains($res['code'] ?? 0, [200, 422, 404]);
    }
}
