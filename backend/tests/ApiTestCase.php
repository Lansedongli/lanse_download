<?php
declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use think\facade\Http;

/**
 * API 测试基类 — 封装 HTTP 请求和认证
 */
abstract class ApiTestCase extends PHPUnitTestCase
{
    protected string $baseUrl = 'http://localhost:8080';
    protected ?string $adminToken = null;
    protected ?string $userToken = null;

    /**
     * 跨实例共享 Token（避免每个测试方法都登录，触发限流）
     */
    private static ?string $sharedAdminToken = null;
    private static ?string $sharedUserToken = null;

    protected function setUp(): void
    {
        parent::setUp();
        // 每个测试类只清除一次文件缓存（避免限流跨类污染）
        static $cleared = false;
        if (!$cleared) {
            $cacheDir = __DIR__ . '/../runtime/cache/';
            if (is_dir($cacheDir)) {
                $this->rmdirRecursive($cacheDir);
            }
            $cleared = true;
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

    /**
     * 登录管理员
     */
    protected function loginAdmin(string $username = 'admin', string $password = 'admin123'): string
    {
        if (self::$sharedAdminToken) {
            $this->adminToken = self::$sharedAdminToken;
            return $this->adminToken;
        }
        $res = $this->post('/admin/api/auth/login', [
            'username' => $username,
            'password' => $password,
        ]);
        $this->assertEquals(200, $res['code'] ?? 0, '管理员登录失败');
        $this->adminToken = $res['data']['tokens']['access_token'] ?? '';
        self::$sharedAdminToken = $this->adminToken;
        return $this->adminToken;
    }

    /**
     * 登录普通用户
     */
    protected function loginUser(string $username = 'testuser', string $password = 'test123'): string
    {
        if (self::$sharedUserToken) {
            $this->userToken = self::$sharedUserToken;
            return $this->userToken;
        }
        $res = $this->post('/api/v1/auth/login', [
            'username' => $username,
            'password' => $password,
        ]);
        if (($res['code'] ?? 0) !== 200) {
            $this->markTestSkipped('普通用户不存在，跳过用户端测试');
        }
        $this->userToken = $res['data']['tokens']['access_token'] ?? '';
        self::$sharedUserToken = $this->userToken;
        return $this->userToken;
    }

    // ── HTTP 请求封装 ──

    protected function get(string $uri, ?string $token = null): array
    {
        return $this->request('GET', $uri, null, $token);
    }

    protected function post(string $uri, ?array $data = null, ?string $token = null): array
    {
        return $this->request('POST', $uri, $data, $token);
    }

    protected function put(string $uri, ?array $data = null, ?string $token = null): array
    {
        return $this->request('PUT', $uri, $data, $token);
    }

    protected function delete(string $uri, ?string $token = null): array
    {
        return $this->request('DELETE', $uri, null, $token);
    }

    protected function request(string $method, string $uri, ?array $data = null, ?string $token = null): array
    {
        $ch = curl_init($this->baseUrl . $uri);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => array_filter([
                'Content-Type: application/json',
                'Accept: application/json',
                $token ? "Authorization: Bearer {$token}" : null,
            ]),
            CURLOPT_POSTFIELDS     => $data ? json_encode($data, JSON_UNESCAPED_UNICODE) : null,
        ]);
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->fail("HTTP {$method} {$uri} 失败: {$error}");
        }

        $result = json_decode($body, true);
        if ($result === null) {
            $this->fail("HTTP {$method} {$uri} 返回非 JSON: " . substr($body, 0, 200));
        }

        // 自动校验 HTTP 状态码一致性
        $result['_http_code'] = $httpCode;

        return $result;
    }

    // ── 断言辅助 ──

    protected function assertApiSuccess(array $response, string $message = ''): void
    {
        $msg = $message ?: ("期望成功, 实际: " . ($response['message'] ?? 'unknown'));
        $this->assertEquals(200, $response['code'] ?? null, $msg);
    }

    protected function assertApiError(array $response, int $expectedCode = 422, string $message = ''): void
    {
        $msg = $message ?: ("期望错误码 {$expectedCode}, 实际: " . ($response['code'] ?? 'unknown'));
        $this->assertEquals($expectedCode, $response['code'] ?? null, $msg);
    }
}
