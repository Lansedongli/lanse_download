<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\IntegrationConfig;
use app\common\model\User as UserModel;
use app\common\service\JwtService;
use think\facade\Db;

/**
 * 万能会员整合服务
 * 负责连接第三方MySQL数据库，验证用户、同步用户信息
 */
class IntegrationService
{
    private IntegrationConfig $configModel;
    private UserModel $userModel;
    private JwtService $jwtService;

    /** @var array 外部数据库连接缓存 [configId => PDO] */
    private array $connections = [];

    public function __construct(IntegrationConfig $configModel, UserModel $userModel, JwtService $jwtService)
    {
        $this->configModel = $configModel;
        $this->userModel   = $userModel;
        $this->jwtService  = $jwtService;
    }

    // ============================================================
    // 公共方法
    // ============================================================

    /**
     * 获取所有启用的配置
     * @return array
     */
    public function getConfigs(): array
    {
        return $this->configModel->where('status', 1)
            ->hidden(['db_password'])
            ->select()
            ->toArray();
    }

    /**
     * 根据ID获取配置
     * @param int $configId
     * @return array|null
     */
    public function getConfigById(int $configId): ?array
    {
        $config = $this->configModel->find($configId);
        return $config ? $config->toArray() : null;
    }

    /**
     * 验证外部用户
     * @param string $username 用户名
     * @param string $password 明文密码
     * @param int    $configId 配置ID
     * @return array|null 外部用户数据，失败返回null
     */
    public function verifyUser(string $username, string $password, int $configId): ?array
    {
        $config = $this->getConfigById($configId);
        if (!$config || $config['status'] != 1) {
            throw new \RuntimeException('接口配置不存在或已禁用');
        }

        $conn = $this->getExternalConnection($config);

        // 查询外部用户
        $stmt = $conn->prepare(
            "SELECT * FROM `{$config['user_table']}` WHERE `{$config['username_field']}` = ? LIMIT 1"
        );
        $stmt->execute([$username]);
        $externalUser = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$externalUser) {
            return null; // 用户不存在
        }

        // 验证密码
        $hashField = $config['password_field'];
        $storedHash = $externalUser[$hashField] ?? '';

        if (!$this->verifyPassword($password, $storedHash, $config['password_hash'])) {
            return null; // 密码错误
        }

        return $externalUser;
    }

    /**
     * 获取外部用户信息（不验证密码）
     * @param string $username
     * @param int    $configId
     * @return array|null
     */
    public function getUserInfo(string $username, int $configId): ?array
    {
        $config = $this->getConfigById($configId);
        if (!$config || $config['status'] != 1) {
            throw new \RuntimeException('接口配置不存在或已禁用');
        }

        $conn = $this->getExternalConnection($config);

        $stmt = $conn->prepare(
            "SELECT * FROM `{$config['user_table']}` WHERE `{$config['username_field']}` = ? LIMIT 1"
        );
        $stmt->execute([$username]);
        $externalUser = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $externalUser ?: null;
    }

    /**
     * 同步外部用户到本地系统（如不存在则创建）
     * @param string $username
     * @param int    $configId
     * @return array 本地用户数据
     */
    public function syncUser(string $username, int $configId): array
    {
        $config = $this->getConfigById($configId);
        if (!$config || $config['status'] != 1) {
            throw new \RuntimeException('接口配置不存在或已禁用');
        }

        // 先查本地是否已存在
        $localUser = $this->userModel->where('username', $username)->find();

        if ($localUser) {
            // 本地用户已存在，同步外部信息
            $externalUser = $this->getUserInfo($username, $configId);
            if ($externalUser) {
                $syncData = [];

                // 同步会员组
                $groupField = $config['group_field'];
                if (!empty($groupField) && isset($externalUser[$groupField])) {
                    $syncData['group_id'] = (int) $externalUser[$groupField];
                }

                // 同步积分
                $pointsField = $config['points_field'];
                if (!empty($pointsField) && isset($externalUser[$pointsField])) {
                    $syncData['points'] = (int) $externalUser[$pointsField];
                }

                if (!empty($syncData)) {
                    $this->userModel->where('id', $localUser->id)->update($syncData);
                }
            }

            return $localUser->toArray();
        }

        // 本地不存在，获取外部用户创建本地账号
        $externalUser = $this->getUserInfo($username, $configId);
        if (!$externalUser) {
            throw new \RuntimeException('外部用户不存在');
        }

        $localUser = $this->userModel->create([
            'username'   => $externalUser[$config['username_field']] ?? $username,
            'password'   => password_hash(
                $externalUser[$config['password_field']] ?? $username . '@edown',
                PASSWORD_BCRYPT
            ),
            'email'      => ($externalUser['email'] ?? '') ?: $username . '@integration.local',
            'group_id'   => !empty($config['group_field']) ? (int) ($externalUser[$config['group_field']] ?? 1) : 1,
            'points'     => !empty($config['points_field']) ? (int) ($externalUser[$config['points_field']] ?? 0) : 0,
            'status'     => 1,
        ]);

        return $localUser->toArray();
    }

    /**
     * 完整登录流程：验证外部用户 → 同步本地 → 签发JWT
     * @param string $username
     * @param string $password
     * @param int    $configId
     * @return array [user, tokens]
     */
    public function login(string $username, string $password, int $configId): array
    {
        // 1. 验证外部用户
        $externalUser = $this->verifyUser($username, $password, $configId);
        if (!$externalUser) {
            throw new \RuntimeException('用户名或密码错误');
        }

        // 2. 同步/创建本地用户
        $localUser = $this->syncUser($username, $configId);

        // 3. 签发 JWT
        $tokens = $this->jwtService->createToken([
            'id'       => $localUser['id'],
            'username' => $localUser['username'],
            'group_id' => $localUser['group_id'] ?? 0,
            'level'    => $localUser['level'] ?? 1,
        ]);

        return [
            'user'   => [
                'id'        => $localUser['id'],
                'username'  => $localUser['username'],
                'email'     => $localUser['email'] ?? '',
                'nickname'  => $localUser['nickname'] ?? '',
                'avatar'    => $localUser['avatar'] ?? '',
                'group_id'  => $localUser['group_id'] ?? 0,
                'points'    => $localUser['points'] ?? 0,
                'expire_time' => $localUser['expire_time'] ?? null,
            ],
            'tokens' => $tokens,
        ];
    }

    /**
     * 测试连接
     * @param int $configId
     * @return array [success, message, server_info]
     */
    public function testConnection(int $configId): array
    {
        $config = $this->getConfigById($configId);
        if (!$config) {
            return ['success' => false, 'message' => '配置不存在', 'server_info' => null];
        }

        try {
            $conn = $this->getExternalConnection($config);
            $stmt = $conn->query("SELECT VERSION() as version");
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            // 同时测试表是否存在
            $tableCheck = $conn->query("SELECT 1 FROM `{$config['user_table']}` LIMIT 1");

            return [
                'success'     => true,
                'message'     => '连接成功',
                'server_info' => [
                    'version'    => $row['version'] ?? 'unknown',
                    'table'      => $config['user_table'],
                    'table_ok'   => true,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success'     => false,
                'message'     => '连接失败: ' . $e->getMessage(),
                'server_info' => null,
            ];
        }
    }

    // ============================================================
    // 私有辅助方法
    // ============================================================

    /**
     * 获取外部数据库连接（带缓存）
     * @param array $config
     * @return \PDO
     */
    private function getExternalConnection(array $config): \PDO
    {
        $cacheKey = (int) $config['id'];

        if (isset($this->connections[$cacheKey])) {
            // 检查连接是否还活着
            try {
                $this->connections[$cacheKey]->query('SELECT 1');
            } catch (\PDOException $e) {
                unset($this->connections[$cacheKey]);
            }
        }

        if (!isset($this->connections[$cacheKey])) {
            $charset = $config['db_charset'] ?? 'utf8mb4';
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['db_host'],
                (int) ($config['db_port'] ?? 3306),
                $config['db_name'],
                $charset
            );

            $this->connections[$cacheKey] = new \PDO(
                $dsn,
                $config['db_user'],
                $config['db_password'],
                [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_TIMEOUT            => 5,
                ]
            );
        }

        return $this->connections[$cacheKey];
    }

    /**
     * 验证密码
     * @param string $input    用户输入的明文密码
     * @param string $stored   存储的哈希值
     * @param string $hashType 哈希类型: md5 / sha1 / bcrypt / plain
     * @return bool
     */
    private function verifyPassword(string $input, string $stored, string $hashType): bool
    {
        return match (strtolower($hashType)) {
            'md5'    => md5($input) === $stored,
            'sha1'   => sha1($input) === $stored,
            'bcrypt' => password_verify($input, $stored),
            'plain'  => $input === $stored,
            default  => false,
        };
    }

    /**
     * 关闭所有外部连接（析构时可调用）
     */
    public function closeConnections(): void
    {
        $this->connections = [];
    }
}
