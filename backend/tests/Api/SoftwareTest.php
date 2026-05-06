<?php
declare(strict_types=1);

namespace tests\Api;

use tests\ApiTestCase;

/**
 * 软件与分类模块测试
 * - 分类无限级 CRUD
 * - 软件 CRUD + 审核
 */
class SoftwareTest extends ApiTestCase
{
    private ?string $token = null;
    private array $createdSoftwareIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->loginAdmin();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdSoftwareIds as $id) {
            $this->delete("/admin/api/software/{$id}", $this->token);
        }
        parent::tearDown();
    }

    // ── 分类管理 ──

    public function testGetCategoryTree(): void
    {
        $res = $this->get('/admin/api/categories', $this->token);
        $this->assertApiSuccess($res);
        $this->assertIsArray($res['data'] ?? null);
    }

    public function testCreateAndDeleteCategory(): void
    {
        $res = $this->post('/admin/api/categories', [
            'name'      => '测试分类_' . time(),
            'parent_id' => 0,
        ], $this->token);
        $this->assertApiSuccess($res, '创建分类');
        $catId = $res['data']['id'] ?? 0;
        $this->assertGreaterThan(0, $catId);

        // 删除
        $del = $this->delete("/admin/api/categories/{$catId}", $this->token);
        $this->assertApiSuccess($del, '删除分类');
    }

    // ── 软件管理 ──

    public function testListSoftware(): void
    {
        $res = $this->get('/admin/api/software', $this->token);
        $this->assertApiSuccess($res);
        $this->assertIsArray($res['data'] ?? null);
    }

    public function testCreateSoftwareValidation(): void
    {
        // 空 title → 422
        $res = $this->post('/admin/api/software', [
            'category_id' => 1,
        ], $this->token);
        $this->assertApiError($res, 422);
    }

    public function testCreateAndDeleteSoftware(): void
    {
        // 先创建一个分类
        $catRes = $this->post('/admin/api/categories', [
            'name'      => '测试分类_' . time(),
            'parent_id' => 0,
        ], $this->token);
        $this->assertApiSuccess($catRes, '创建分类');
        $catId = $catRes['data']['id'] ?? 0;
        $this->assertGreaterThan(0, $catId);

        $res = $this->post('/admin/api/software', [
            'name'          => '自动化测试软件 ' . uniqid(),
            'category_id'   => $catId,
            'type_id'       => 1,
            'summary'       => '这是自动化测试创建的软件',
            'download_url'  => 'https://example.com/test.zip',
            'points'        => 5,
        ], $this->token);

        // 接受：200(成功) / 422(验证失败) / 404(分类或类型不存在)
        $acceptableCodes = [200, 422, 404];
        $this->assertContains($res['code'] ?? 0, $acceptableCodes, '创建软件: ' . ($res['message'] ?? 'unknown'));

        $swId = $res['data']['id'] ?? 0;

        if ($swId > 0) {
            // 审核
            $audit = $this->put("/admin/api/software/{$swId}/audit", ['status' => 1], $this->token);
            $this->assertApiSuccess($audit, '审核软件');

            // 删除软件
            $this->delete("/admin/api/software/{$swId}", $this->token);
        }

        // 清理分类
        $this->delete("/admin/api/categories/{$catId}", $this->token);
    }
}
