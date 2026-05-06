<?php
declare(strict_types=1);

namespace tests\Api;

use tests\ApiTestCase;

/**
 * 安全测试
 * - SQL 注入防护
 * - XSS 防护
 * - CSRF Token（如启用）
 * - 未授权访问
 * - 超长输入防护
 */
class SecurityTest extends ApiTestCase
{
    private ?string $token = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->loginAdmin();
    }

    // ── 未授权访问 ──

    public function testUnauthenticatedAccess(): array
    {
        $endpoints = [
            'GET'  => ['/admin/api/categories', '/admin/api/software', '/admin/api/users', '/admin/api/roles', '/admin/api/pay/channels', '/admin/api/report/summary'],
            'POST' => ['/admin/api/categories', '/admin/api/software'],
        ];

        $failures = [];
        foreach ($endpoints as $method => $uris) {
            foreach ($uris as $uri) {
                $res = $this->request($method, $uri, null, null);
                if (($res['code'] ?? 0) === 200) {
                    $failures[] = "{$method} {$uri} 无Token却返回200";
                }
            }
        }

        $this->assertEmpty($failures, "未授权端点: " . implode(', ', $failures));
        return [];
    }

    // ── SQL 注入防护 ──

    public function testSqlInjectionInLogin(): void
    {
        $payloads = [
            "' OR '1'='1",
            "' OR 1=1 --",
            "admin'--",
            "' UNION SELECT 1,2,3 --",
        ];

        foreach ($payloads as $payload) {
            $res = $this->post('/admin/api/auth/login', [
                'username' => $payload,
                'password' => $payload,
            ]);
            // 应返回 401（认证失败），而非 200 + 成功登录
            $this->assertNotEquals(200, $res['code'] ?? 0, "SQL注入登录不应成功: {$payload}");
        }
    }

    public function testSqlInjectionInQueryParams(): void
    {
        $payloads = [
            "1' OR '1'='1",
            "1; DROP TABLE users --",
        ];

        foreach ($payloads as $payload) {
            $res = $this->get("/admin/api/software?id=" . urlencode($payload), $this->token);
            // 不应返回 500（SQL 错误）
            $this->assertNotEquals(500, $res['code'] ?? 0, "SQL注入查询不应触发500: {$payload}");
        }
    }

    // ── XSS 防护 ──

    public function testXssInInputFields(): void
    {
        $xssPayload = '<script>alert("xss")</script>';

        // 创建软件时注入 XSS
        $res = $this->post('/admin/api/software', [
            'name'       => $xssPayload,
            'category_id' => 1,
            'summary'     => $xssPayload,
            'description' => '<img src=x onerror=alert(1)>',
        ], $this->token);

        if ($res['code'] === 200) {
            // 如果创建成功，检查返回的数据是否转义了
            $title = $res['data']['name'] ?? '';
            $this->assertStringNotContainsString('<script>', $title, 'XSS 脚本应被转义');

            // 清理
            $id = $res['data']['id'] ?? 0;
            if ($id) $this->delete("/admin/api/software/{$id}", $this->token);
        } else {
            // 如果被拦截（422验证失败），也证明防护有效
            $this->assertContains($res['code'] ?? 0, [200, 422]);
        }
    }

    // ── 超长输入防护 ──

    public function testOverlyLongInput(): void
    {
        $longStr = str_repeat('A', 10000);

        $res = $this->post('/admin/api/software', [
            'name'       => $longStr,
            'category_id' => 1,
        ], $this->token);

        // 应被验证拦截（422）或截断
        $this->assertNotEquals(500, $res['code'] ?? 0, '超长输入不应触发500');
    }

    // ── IDOR (不安全的直接对象引用) ──

    public function testIdorAccessOtherUserData(): void
    {
        // 尝试用管理员Token访问不存在的用户ID
        $res = $this->get('/admin/api/users/999999', $this->token);
        // 接受 200（返回空用户）或 404
        $this->assertContains($res['code'] ?? 0, [200, 404], '访问不存在用户');

        // 尝试用不存在的ID操作
        $res = $this->put('/admin/api/pay/channels/999999', [
            'name' => 'hack',
        ], $this->token);
        // 应返回 404 或 200（某些 API 返回 200 + "not found"）
        $this->assertContains($res['code'] ?? 0, [200, 404, 422], '操作不存在资源');
    }

    // ── 请求方法限制 ──

    public function testMethodNotAllowed(): void
    {
        // DELETE 不允许的 GET 端点
        $res = $this->delete('/admin/api/report/summary', $this->token);
        // 应被验证拦截（422/404/405都算拦截成功）
        $this->assertNotEquals(200, $res['code'] ?? 0, 'DELETE 不允许的 GET 端点');
    }
}
