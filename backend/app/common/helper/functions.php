<?php
declare(strict_types=1);

if (!function_exists('generate_order_no')) {
    /**
     * 生成唯一订单号
     */
    function generate_order_no(string $prefix = 'ED'): string
    {
        return $prefix . date('YmdHis') . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 6));
    }
}

if (!function_exists('generate_card_no')) {
    /**
     * 生成点卡卡号
     */
    function generate_card_no(string $batchNo, int $index): string
    {
        return strtoupper($batchNo . str_pad((string) $index, 8, '0', STR_PAD_LEFT));
    }
}

if (!function_exists('generate_password')) {
    /**
     * 生成随机密码
     */
    function generate_password(int $length = 16): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }
}

if (!function_exists('download_token')) {
    /**
     * 生成下载防盗链 Token（HMAC-SHA256 签名）
     */
    function download_token(int $softwareId, int $attachmentId, int $userId, int $ttl = 300): string
    {
        $payload = [
            'software_id'   => $softwareId,
            'attachment_id' => $attachmentId,
            'user_id'       => $userId,
            'exp'           => time() + $ttl,
        ];
        $secret = config('jwt.secret');
        $json = json_encode($payload);
        $hmac = hash_hmac('sha256', $json, $secret);
        return base64_encode($json . '.' . $hmac);
    }
}

if (!function_exists('verify_download_token')) {
    /**
     * 验证下载 Token（HMAC-SHA256 签名验证）
     */
    function verify_download_token(string $token): ?array
    {
        $secret = config('jwt.secret');
        $decoded = base64_decode($token, true);
        if (!$decoded || !str_contains($decoded, '.')) {
            return null;
        }

        [$json, $hmac] = explode('.', $decoded, 2);
        // 使用 hash_equals 防时序攻击
        if (!hash_equals(hash_hmac('sha256', $json, $secret), $hmac)) {
            return null;
        }

        $payload = json_decode($json, true);
        if (!$payload || ($payload['exp'] ?? 0) < time()) {
            return null;
        }

        return $payload;
    }
}
