<?php
declare(strict_types=1);

namespace app\common\service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use think\facade\Cache;

/**
 * JWT 认证服务
 * 负责 Token 生成、验证、刷新、黑名单
 */
class JwtService
{
    private string $secret;
    private int $ttl;
    private int $refreshTtl;
    private string $algo;
    private string $issuer;

    public function __construct()
    {
        $config = config('jwt');
        $this->secret     = $config['secret'] ?? '';
        $this->ttl        = $config['ttl'] ?? 7200;
        $this->refreshTtl = $config['refresh_ttl'] ?? 1209600;
        $this->algo       = $config['algo'] ?? 'HS256';
        $this->issuer     = $config['issuer'] ?? 'empiredown';

        // 安全校验：JWT secret 不能为空
        if (empty($this->secret) || $this->secret === 'ChangeMeToARandomBase64String') {
            throw new \RuntimeException(
                'JWT_SECRET 未配置或使用默认值，请在 .env 中设置安全的 JWT_SECRET（使用 openssl rand -base64 64 生成）'
            );
        }
    }

    /**
     * 生成 Access Token
     */
    public function createToken(array $user, string $guard = 'api'): array
    {
        $now = time();
        $accessPayload = [
            'iss'       => $this->issuer,
            'iat'       => $now,
            'exp'       => $now + $this->ttl,
            'nbf'       => $now,
            'guard'     => $guard,
            'user_id'   => $user['id'],
            'username'  => $user['username'] ?? '',
            'role_id'   => $user['role_id'] ?? 0,
            'group_id'  => $user['group_id'] ?? 0,
            'level'     => $user['level'] ?? 1,
        ];

        $refreshPayload = [
            'iss'       => $this->issuer,
            'iat'       => $now,
            'exp'       => $now + $this->refreshTtl,
            'type'      => 'refresh',
            'guard'     => $guard,
            'user_id'   => $user['id'],
            'username'  => $user['username'] ?? '',
            'group_id'  => $user['group_id'] ?? 0,
            'level'     => $user['level'] ?? 1,
        ];

        $accessToken  = JWT::encode($accessPayload, $this->secret, $this->algo);
        $refreshToken = JWT::encode($refreshPayload, $this->secret, $this->algo);

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in'    => $this->ttl,
            'token_type'    => 'Bearer',
        ];
    }

    /**
     * 验证 Token
     */
    public function verifyToken(string $token): ?array
    {
        // 检查黑名单
        if ($this->isBlacklisted($token)) {
            return null;
        }

        try {
            $decoded = (array) JWT::decode($token, new Key($this->secret, $this->algo));
            return $decoded;
        } catch (ExpiredException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 刷新 Token
     */
    public function refreshToken(string $refreshToken): ?array
    {
        $payload = $this->verifyToken($refreshToken);
        if (!$payload || ($payload['type'] ?? '') !== 'refresh') {
            return null;
        }

        // 将旧refresh加入黑名单
        $this->addToBlacklist($refreshToken, $this->refreshTtl);

        return $this->createToken([
            'id'       => $payload['user_id'],
            'username' => $payload['username'] ?? '',
            'group_id' => $payload['group_id'] ?? 0,
            'level'    => $payload['level'] ?? 1,
        ], $payload['guard'] ?? 'api');
    }

    /**
     * 废除 Token (退出登录用)
     */
    public function revokeToken(string $token): void
    {
        $payload = $this->verifyToken($token);
        if ($payload) {
            $remaining = max(0, ($payload['exp'] ?? time()) - time());
            $this->addToBlacklist($token, $remaining);
        }
    }

    /**
     * Token 加入黑名单
     */
    private function addToBlacklist(string $token, int $ttl): void
    {
        $key = 'jwt_blacklist:' . md5($token);
        Cache::set($key, 1, $ttl);
    }

    /**
     * 检查是否在黑名单
     */
    private function isBlacklisted(string $token): bool
    {
        $key = 'jwt_blacklist:' . md5($token);
        return (bool) Cache::get($key);
    }
}
