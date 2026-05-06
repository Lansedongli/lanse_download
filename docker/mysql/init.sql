-- ============================================
-- 帝国下载系统 数据库初始化脚本
-- 在 Docker 首次启动时自动执行
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 角色表
CREATE TABLE IF NOT EXISTS `role` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL COMMENT '角色名称',
    `code` VARCHAR(50) NOT NULL COMMENT '角色标识',
    `description` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '角色描述',
    `permissions` JSON NULL COMMENT '权限列表',
    `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态:1启用0禁用',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色表';

-- 管理员表
CREATE TABLE IF NOT EXISTS `admin_user` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL COMMENT '用户名',
    `password` VARCHAR(255) NOT NULL COMMENT '密码哈希',
    `nickname` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '昵称',
    `avatar` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '头像',
    `email` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '邮箱',
    `mobile` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '手机号',
    `role_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '角色ID',
    `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态:1正常0禁用',
    `login_ip` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '最后登录IP',
    `login_time` DATETIME NULL COMMENT '最后登录时间',
    `login_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '登录次数',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_username` (`username`),
    KEY `idx_role_id` (`role_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员表';

-- 初始角色
INSERT INTO `role` (`name`, `code`, `description`, `permissions`, `status`, `sort`) VALUES
('超级管理员', 'super_admin', '拥有所有权限', '["*"]', 1, 0),
('内容编辑', 'editor', '内容编辑权限', '["software:list","software:create","software:update","category:list","category:create"]', 1, 0);

-- 默认管理员（密码 admin123）
INSERT INTO `admin_user` (`username`, `password`, `nickname`, `role_id`, `status`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '系统管理员', 1, 1);

-- 操作日志表
CREATE TABLE IF NOT EXISTS `operation_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作人ID',
    `username` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '操作人用户名',
    `module` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '操作模块',
    `action` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '操作动作',
    `method` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '请求方法',
    `path` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '请求路径',
    `params` TEXT NULL COMMENT '请求参数(已脱敏)',
    `ip` VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'IP地址',
    `user_agent` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '用户代理',
    `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1成功0失败',
    `error_msg` VARCHAR(1000) NOT NULL DEFAULT '' COMMENT '错误信息',
    `duration_ms` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '耗时(毫秒)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_module` (`module`),
    KEY `idx_action` (`action`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志表';

-- 登录日志表
CREATE TABLE IF NOT EXISTS `user_login_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `username` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '用户名',
    `guard` VARCHAR(20) NOT NULL DEFAULT 'api' COMMENT '守卫:admin/api',
    `ip` VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'IP地址',
    `user_agent` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '用户代理',
    `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1成功0失败',
    `error_msg` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '错误信息',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_username` (`username`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='登录日志表';

SET FOREIGN_KEY_CHECKS = 1;
