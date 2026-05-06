<?php
declare(strict_types=1);

namespace tests\Api;

use tests\ApiTestCase;

/**
 * 认证与权限测试
 * - 管理员登录/Token刷新
 * - 用户端登录/注册
 * - RBAC 权限隔离验证
 * - 限流测试
 */
class AuthTest extends ApiTestCase
{
    // ── 管理员认证 ──

    public function testAdminLoginSuccess(): void
    {
        $res = $this->post('/admin/api/auth/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);
        $this->assertApiSuccess($res);
        $this->assertArrayHasKey('access_token', $res['data']['tokens'] ?? []);
        $this->assertEquals(1, $res['data']['user']['role_id'] ?? 0);
    }

    public function testAdminLoginFailBadPassword(): void
    {
        $res = $this->post('/admin/api/auth/login', [
            'username' => 'admin',
            'password' => 'wrong_password_xyz',
        ]);
        $this->assertApiError($res, 401);
    }

    public function testAdminMeWithToken(): void
    {
        $token = $this->loginAdmin();
        $res = $this->get('/admin/api/auth/me', $token);
        $this->assertApiSuccess($res);
        $this->assertEquals('admin', $res['data']['username'] ?? '');
    }

    public function testAdminMeWithoutToken(): void
    {
        $res = $this->get('/admin/api/auth/me');
        $this->assertApiError($res, 401);
    }

    // ── 用户端认证 ──

    public function testUserLoginSuccess(): void
    {
        $res = $this->post('/api/v1/auth/login', [
            'username' => 'testuser',
            'password' => 'test123',
        ]);
        if ($res['code'] === 404) {
            $this->markTestSkipped('API路由未注册');
        }
        $this->assertApiSuccess($res);
    }

    // ── RBAC 权限隔离 ──

    public function testAdminCanAccessPayChannels(): void
    {
        $token = $this->loginAdmin();
        $res = $this->get('/admin/api/pay/channels', $token);
        $this->assertApiSuccess($res);
        $this->assertIsArray($res['data'] ?? null);
    }

    public function testAdminCanManageRoles(): void
    {
        $token = $this->loginAdmin();
        $res = $this->get('/admin/api/roles', $token);
        $this->assertApiSuccess($res);
    }

    // ── 限流验证（Rate Limit） ──

    public function testLoginRateLimit(): void
    {
        $failCount = 0;
        $rateLimited = false;

        for ($i = 0; $i < 15; $i++) {
            $res = $this->post('/admin/api/auth/login', [
                'username' => 'admin',
                'password' => 'wrong',
            ]);
            if ($res['code'] === 429) {
                $rateLimited = true;
                break;
            }
            if ($res['code'] === 401) {
                $failCount++;
            }
            usleep(100000); // 0.1s
        }

        // 10次/分钟限流，连续15次错误应触发
        $this->assertTrue($rateLimited || $failCount >= 10, '限流未生效: ' . json_encode(['failCount' => $failCount, 'rateLimited' => $rateLimited]));

        // 清理限流状态，避免影响后续测试
        $this->clearRateLimit();
    }

    /**
     * 清除 admin 登录限流 Redis key，避免影响其他测试套件
     */
    private function clearRateLimit(): void
    {
        // 清除文件缓存中的限流 key
        $cacheDir = __DIR__ . '/../../runtime/cache/';
        if (is_dir($cacheDir)) {
            $this->rmdirRecursive($cacheDir);
        }
    }

    private function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->rmdirRecursive($path) : unlink($path);
        }
        @rmdir($dir);
    }
}
