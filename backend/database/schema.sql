-- ================================================================
-- 帝国下载系统 V3.0 数据库结构设计
-- 技术栈：ThinkPHP 8.1 + PHP 8.1 + MySQL 8.0
-- 编码：utf8mb4 / utf8mb4_unicode_ci
-- 引擎：InnoDB
-- 日期：2026-05-05
-- ================================================================

CREATE DATABASE IF NOT EXISTS `empiredown` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `empiredown`;

-- ================================================================
-- 一、系统配置与基础模块
-- ================================================================

-- 1.1 系统配置表（KV键值对）
CREATE TABLE `system_config` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `group`         VARCHAR(50)     NOT NULL DEFAULT 'base' COMMENT '配置分组(base/member/payment/download/email/upload/security)',
    `key`           VARCHAR(100)    NOT NULL COMMENT '配置键名',
    `value`         TEXT            NULL     COMMENT '配置值',
    `type`          VARCHAR(20)     NOT NULL DEFAULT 'string' COMMENT '值类型(string/int/float/bool/json/array)',
    `title`         VARCHAR(200)    NOT NULL DEFAULT '' COMMENT '配置标题（中文说明）',
    `description`   VARCHAR(500)    NOT NULL DEFAULT '' COMMENT '配置描述',
    `sort`          INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '排序(越小越前)',
    `is_system`     TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '是否系统级配置(1是 0否,系统级不可删除)',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_group_key` (`group`, `key`),
    KEY `idx_group` (`group`),
    KEY `idx_sort` (`sort`)
) ENGINE=InnoDB COMMENT='系统配置表(键值对)';


-- 1.2 后台管理员表
CREATE TABLE `admin_user` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `username`      VARCHAR(50)     NOT NULL COMMENT '用户名',
    `password`      VARCHAR(255)    NOT NULL COMMENT '密码(bcrypt哈希)',
    `nickname`      VARCHAR(100)    NOT NULL DEFAULT '' COMMENT '昵称',
    `avatar`        VARCHAR(500)    NOT NULL DEFAULT '' COMMENT '头像URL',
    `email`         VARCHAR(100)    NOT NULL DEFAULT '' COMMENT '邮箱',
    `mobile`        VARCHAR(20)     NOT NULL DEFAULT '' COMMENT '手机号',
    `role_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '角色ID',
    `status`        TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '状态(1正常 0禁用)',
    `login_ip`      VARCHAR(50)     NOT NULL DEFAULT '' COMMENT '最后登录IP',
    `login_time`    DATETIME        NULL     COMMENT '最后登录时间',
    `login_count`   INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '登录次数',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_username` (`username`),
    KEY `idx_role_id` (`role_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB COMMENT='后台管理员表';


-- 1.3 角色表（RBAC）
CREATE TABLE `role` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `name`          VARCHAR(50)     NOT NULL COMMENT '角色名称',
    `code`          VARCHAR(50)     NOT NULL COMMENT '角色标识(super_admin/editor/auditor)',
    `description`   VARCHAR(500)    NOT NULL DEFAULT '' COMMENT '角色描述',
    `permissions`   JSON            NULL     COMMENT '权限JSON数组(如["software:list","software:create"])',
    `status`        TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '状态(1正常 0禁用)',
    `sort`          INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '排序',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB COMMENT='角色表(RBAC)';


-- ================================================================
-- 二、会员系统模块（核心收费体系）
-- ================================================================

-- 2.1 会员组表
CREATE TABLE `user_group` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `name`              VARCHAR(100)    NOT NULL COMMENT '会员组名称(如:普通会员/VIP/SVIP)',
    `code`              VARCHAR(50)     NOT NULL COMMENT '会员组标识(normal/vip/svip)',
    `level`             INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '会员级别(数字越大级别越高)',
    `is_default`        TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '是否默认会员组(1是 0否)',
    `price_month`       DECIMAL(10,2)   NOT NULL DEFAULT 0.00 COMMENT '包月价格',
    `price_quarter`     DECIMAL(10,2)   NOT NULL DEFAULT 0.00 COMMENT '包季价格',
    `price_year`        DECIMAL(10,2)   NOT NULL DEFAULT 0.00 COMMENT '包年价格',
    `max_favorites`     INT UNSIGNED    NOT NULL DEFAULT 100 COMMENT '最大收藏夹数',
    `max_download_day`  INT UNSIGNED    NOT NULL DEFAULT 10 COMMENT '每日最大下载数(0不限)',
    `discount`          DECIMAL(3,2)    NOT NULL DEFAULT 1.00 COMMENT '下载点数折扣(1.00=原价)',
    `icon`              VARCHAR(500)    NOT NULL DEFAULT '' COMMENT '会员组图标URL',
    `description`       TEXT            NULL     COMMENT '会员组描述/特权说明',
    `status`            TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '状态(1启用 0禁用)',
    `sort`              INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '排序',
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`),
    KEY `idx_level` (`level`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB COMMENT='会员组表';


-- 2.2 会员用户表（前台注册用户）
CREATE TABLE `user` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `username`          VARCHAR(50)     NOT NULL COMMENT '用户名',
    `password`          VARCHAR(255)    NOT NULL COMMENT '密码(bcrypt哈希)',
    `email`             VARCHAR(100)    NOT NULL DEFAULT '' COMMENT '邮箱',
    `mobile`            VARCHAR(20)     NOT NULL DEFAULT '' COMMENT '手机号',
    `nickname`          VARCHAR(100)    NOT NULL DEFAULT '' COMMENT '昵称',
    `avatar`            VARCHAR(500)    NOT NULL DEFAULT '' COMMENT '头像URL',
    `group_id`          BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '会员组ID',
    `points`            INT             NOT NULL DEFAULT 0 COMMENT '剩余点数(可正可负,0为无点数)',
    `expire_time`       DATETIME        NULL     COMMENT '会员有效期(到期自动降级为默认组)',
    `total_recharge`    DECIMAL(12,2)   NOT NULL DEFAULT 0.00 COMMENT '累计充值金额',
    `total_download`    INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '累计下载次数',
    `today_download`    INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '今日下载次数(每日0点重置)',
    `register_ip`       VARCHAR(50)     NOT NULL DEFAULT '' COMMENT '注册IP',
    `login_ip`          VARCHAR(50)     NOT NULL DEFAULT '' COMMENT '最后登录IP',
    `login_time`        DATETIME        NULL     COMMENT '最后登录时间',
    `openid_wechat`     VARCHAR(100)    NOT NULL DEFAULT '' COMMENT '微信OpenID',
    `openid_qq`         VARCHAR(100)    NOT NULL DEFAULT '' COMMENT 'QQ OpenID',
    `status`            TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '状态(1正常 0禁用 -1删除)',
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '注册时间',
    `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `deleted_at`        DATETIME        NULL     COMMENT '软删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_username` (`username`),
    UNIQUE KEY `uk_email` (`email`),
    KEY `idx_group_id` (`group_id`),
    KEY `idx_status` (`status`),
    KEY `idx_expire_time` (`expire_time`),
    KEY `idx_openid_wechat` (`openid_wechat`),
    KEY `idx_openid_qq` (`openid_qq`)
) ENGINE=InnoDB COMMENT='会员用户表';


-- 2.3 点数变动日志表
CREATE TABLE `user_points_log` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id`       BIGINT UNSIGNED NOT NULL COMMENT '会员ID',
    `type`          VARCHAR(30)     NOT NULL COMMENT '变动类型(recharge=充值/download=下载扣费/refund=退款/admin_adjust=管理员调整/gift=赠送)',
    `amount`        INT             NOT NULL COMMENT '变动点数(正=增加,负=减少)',
    `balance`       INT             NOT NULL DEFAULT 0 COMMENT '变动后余额',
    `relation_id`   BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联业务ID(充值记录ID/下载记录ID等)',
    `remark`        VARCHAR(500)    NOT NULL DEFAULT '' COMMENT '备注',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '记录时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_type` (`type`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_user_type_time` (`user_id`, `type`, `created_at`)
) ENGINE=InnoDB COMMENT='点数变动日志表';


-- 2.4 用户收藏夹表
CREATE TABLE `user_favorite` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id`       BIGINT UNSIGNED NOT NULL COMMENT '会员ID',
    `software_id`   BIGINT UNSIGNED NOT NULL COMMENT '下载对象ID',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '收藏时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_software` (`user_id`, `software_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_software_id` (`software_id`)
) ENGINE=InnoDB COMMENT='用户收藏夹表';


-- ================================================================
-- 三、支付充值模块
-- ================================================================

-- 3.1 点卡批次表
CREATE TABLE `point_card_batch` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `batch_no`      VARCHAR(50)     NOT NULL COMMENT '批次号',
    `total_count`   INT UNSIGNED    NOT NULL COMMENT '生成总张数',
    `used_count`    INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '已使用张数',
    `points`        INT UNSIGNED    NOT NULL COMMENT '每张卡面点数',
    `expire_days`   INT UNSIGNED    NOT NULL DEFAULT 365 COMMENT '有效期天数(从生成日起算)',
    `group_id`      BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '赠送会员组ID(0表示不赠送)',
    `group_days`    INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '赠送会员天数(0表示不赠送)',
    `created_by`    BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建管理员ID',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_batch_no` (`batch_no`)
) ENGINE=InnoDB COMMENT='点卡批次表';


-- 3.2 点卡明细表
CREATE TABLE `point_card` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `batch_id`      BIGINT UNSIGNED NOT NULL COMMENT '批次ID',
    `card_no`       VARCHAR(50)     NOT NULL COMMENT '卡号',
    `password`      VARCHAR(50)     NOT NULL COMMENT '卡密',
    `points`        INT UNSIGNED    NOT NULL COMMENT '面额点数',
    `group_id`      BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '赠送会员组ID',
    `group_days`    INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '赠送会员天数',
    `expire_time`   DATETIME        NOT NULL COMMENT '过期时间',
    `status`        TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '状态(0未使用 1已使用 2已过期)',
    `used_user_id`  BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用人ID',
    `used_time`     DATETIME        NULL     COMMENT '使用时间',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '生成时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_card_no` (`card_no`),
    KEY `idx_batch_id` (`batch_id`),
    KEY `idx_status` (`status`),
    KEY `idx_used_user_id` (`used_user_id`)
) ENGINE=InnoDB COMMENT='点卡明细表';


-- 3.3 充值记录表
CREATE TABLE `user_recharge` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id`           BIGINT UNSIGNED NOT NULL COMMENT '会员ID',
    `order_no`          VARCHAR(50)     NOT NULL COMMENT '充值订单号(唯一)',
    `type`              VARCHAR(30)     NOT NULL COMMENT '充值类型(point_card=点卡/online_bank=网银/alipay=支付宝/wechat=微信/admin=管理员手动)',
    `amount`            DECIMAL(12,2)   NOT NULL DEFAULT 0.00 COMMENT '充值金额',
    `points`            INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '获得点数',
    `group_id`          BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '获得会员组ID',
    `group_days`        INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '获得会员天数',
    `payment_channel`   VARCHAR(30)     NOT NULL DEFAULT '' COMMENT '支付渠道(具体网关)',
    `trade_no`          VARCHAR(100)    NOT NULL DEFAULT '' COMMENT '第三方交易流水号',
    `status`            TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '状态(0待支付 1已支付 2已取消 3已退款)',
    `paid_at`           DATETIME        NULL     COMMENT '支付时间',
    `remark`            VARCHAR(500)    NOT NULL DEFAULT '' COMMENT '备注',
    `ip`                VARCHAR(50)     NOT NULL DEFAULT '' COMMENT '充值IP',
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_no` (`order_no`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_type` (`type`),
    KEY `idx_trade_no` (`trade_no`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_user_status_time` (`user_id`, `status`, `created_at`)
) ENGINE=InnoDB COMMENT='充值记录表';


-- 3.4 支付渠道配置表
CREATE TABLE `pay_channels` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `name`            VARCHAR(50)     NOT NULL COMMENT '渠道名称(如:支付宝/微信支付)',
    `code`            VARCHAR(30)     NOT NULL COMMENT '渠道代码(alipay/wechat/unionpay)',
    `app_id`          VARCHAR(100)    NOT NULL DEFAULT '' COMMENT 'APP ID / 商户号',
    `mch_id`          VARCHAR(100)    NOT NULL DEFAULT '' COMMENT '商户号(微信支付用)',
    `api_key`         VARCHAR(500)    NOT NULL DEFAULT '' COMMENT 'API密钥/Key',
    `private_key`     TEXT            NULL     COMMENT '应用私钥/商户私钥',
    `public_key`      TEXT            NULL     COMMENT '支付宝公钥/平台证书',
    `notify_url`      VARCHAR(500)    NOT NULL DEFAULT '' COMMENT '异步通知回调URL',
    `return_url`      VARCHAR(500)    NOT NULL DEFAULT '' COMMENT '同步跳转URL',
    `status`          TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '状态(0禁用 1启用)',
    `sort_order`      INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '排序',
    `remark`          VARCHAR(500)    NOT NULL DEFAULT '' COMMENT '备注',
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB COMMENT='支付渠道配置表';


-- 3.5 充值套餐表
CREATE TABLE `recharge_packages` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `name`            VARCHAR(100)    NOT NULL COMMENT '套餐名称',
    `amount`          DECIMAL(12,2)   NOT NULL COMMENT '支付金额(元)',
    `points`          INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '获得点数',
    `bonus_points`    INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '额外赠送点数',
    `group_id`        BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '赠送会员组ID',
    `group_days`      INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '赠送会员天数',
    `icon`            VARCHAR(500)    NOT NULL DEFAULT '' COMMENT '套餐图标',
    `is_hot`          TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '是否热推',
    `status`          TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '状态(0禁用 1启用)',
    `sort_order`      INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '排序',
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_status_sort` (`status`, `sort_order`)
) ENGINE=InnoDB COMMENT='充值套餐表';


INSERT INTO `pay_channels` (`name`, `code`, `app_id`, `status`, `sort_order`, `remark`) VALUES
('支付宝', 'alipay', '', 0, 1, '需在.env中配置 ALIPAY_* 后启用'),
('微信支付', 'wechat', '', 0, 2, '需在.env中配置 WECHAT_* 后启用');

INSERT INTO `recharge_packages` (`name`, `amount`, `points`, `bonus_points`, `is_hot`, `status`, `sort_order`) VALUES
('100点数', 10.00, 100, 0, 0, 1, 1),
('500点数', 50.00, 500, 50, 0, 1, 2),
('1000点数', 100.00, 1000, 150, 0, 1, 3),
('2000点数', 200.00, 2000, 400, 1, 1, 4),
('5000点数', 500.00, 5000, 1500, 0, 1, 5);


-- ================================================================
-- 四、内容管理模块
-- ================================================================

-- 4.1 无限级分类表
CREATE TABLE `category` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `parent_id`     BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父分类ID(0为顶级)',
    `name`          VARCHAR(100)    NOT NULL COMMENT '分类名称',
    `code`          VARCHAR(50)     NOT NULL DEFAULT '' COMMENT '分类标识(URL伪静态用)',
    `path`          VARCHAR(500)    NOT NULL DEFAULT '' COMMENT '层级路径(如:0-1-5-/逗号分隔符)',
    `level`         TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '层级深度',
    `icon`          VARCHAR(500)    NOT NULL DEFAULT '' COMMENT '分类图标',
    `image`         VARCHAR(500)    NOT NULL DEFAULT '' COMMENT '分类封面图',
    `description`   TEXT            NULL     COMMENT '分类描述',
    `template_list` VARCHAR(100)    NOT NULL DEFAULT '' COMMENT '自定义列表页模板',
    `template_detail` VARCHAR(100)  NOT NULL DEFAULT '' COMMENT '自定义详情页模板',
    `seo_title`     VARCHAR(200)    NOT NULL DEFAULT '' COMMENT 'SEO标题',
    `seo_keywords`  VARCHAR(500)    NOT NULL DEFAULT '' COMMENT 'SEO关键词',
    `seo_description` TEXT          NULL     COMMENT 'SEO描述',
    `sort`          INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '排序',
    `status`        TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '状态(1显示 0隐藏)',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_parent_id` (`parent_id`),
    KEY `idx_path` (`path`),
    KEY `idx_code` (`code`),
    KEY `idx_status_sort` (`status`, `sort`)
) ENGINE=InnoDB COMMENT='无限级分类表';


-- 4.2 软件/下载对象主表
CREATE TABLE `software` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `title`             VARCHAR(200)    NOT NULL COMMENT '软件标题',
    `subtitle`          VARCHAR(200)    NOT NULL DEFAULT '' COMMENT '副标题',
    `code`              VARCHAR(50)     NOT NULL DEFAULT '' COMMENT '软件标识(URL伪静态)',
    `category_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属分类ID',
    `type_id`           BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '软件类型ID',
    `language`          VARCHAR(30)     NOT NULL DEFAULT '中文' COMMENT '软件语言(中文/英文/多语言)',
    `license`           VARCHAR(30)     NOT NULL DEFAULT '免费' COMMENT '授权方式(免费/共享/商业/开源)',
    `platform`          VARCHAR(100)    NOT NULL DEFAULT '' COMMENT '运行平台(Windows/Linux/Mac/Android/iOS等)',
    `version`           VARCHAR(50)     NOT NULL DEFAULT '' COMMENT '软件版本号',
    `file_size`         VARCHAR(50)     NOT NULL DEFAULT '' COMMENT '文件大小(如:102.4MB)',
    `author`            VARCHAR(100)    NOT NULL DEFAULT '' COMMENT '作者/开发商',
    `homepage`          VARCHAR(500)    NOT NULL DEFAULT '' COMMENT '官方主页URL',
    `require_points`    INT UNSIGNED    NOT NULL DEFAULT 1 COMMENT '下载所需点数',
    `require_group`     BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '下载所需会员组ID(0不限制)',
    `deduct_interval`   INT UNSIGNED    NOT NULL DEFAULT 1440 COMMENT '重复下载扣费间隔(分钟,1440=24h)',
    `thumbnail`         VARCHAR(500)    NOT NULL DEFAULT '' COMMENT '缩略图URL',
    `images`            JSON            NULL     COMMENT '软件截图(JSON数组)',
    `description`       TEXT            NULL     COMMENT '软件简介(短)',
    `content`           LONGTEXT        NULL     COMMENT '软件详细描述(富文本)',
    `changelog`         TEXT            NULL     COMMENT '更新日志',
    `first_letter`      CHAR(1)         NOT NULL DEFAULT '' COMMENT '首字母(大小写,用于字母导航)',
    `is_recommend`      TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '是否推荐(1是 0否)',
    `is_hot`            TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '是否热门(1是 0否)',
    `is_new`            TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '是否新品(1是 0否)',
    `download_count`    INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '总下载次数(冗余字段,定时同步)',
    `view_count`        INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '浏览次数',
    `comment_count`     INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '评论数(冗余)',
    `rating`            DECIMAL(2,1)    NOT NULL DEFAULT 0.0 COMMENT '评分(0-5,冗余)',
    `rating_count`      INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '评分人数',
    `seo_title`         VARCHAR(200)    NOT NULL DEFAULT '' COMMENT 'SEO标题',
    `seo_keywords`      VARCHAR(500)    NOT NULL DEFAULT '' COMMENT 'SEO关键词',
    `seo_description`   TEXT            NULL     COMMENT 'SEO描述',
    `source`            TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '来源(1管理员发布 2用户投稿)',
    `submit_user_id`    BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '投稿用户ID',
    `status`            TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '状态(0待审核 1已发布 2退回 3草稿)',
    `sort`              INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '排序',
    `published_at`      DATETIME        NULL     COMMENT '发布时间',
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `deleted_at`        DATETIME        NULL     COMMENT '软删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_category_id` (`category_id`),
    KEY `idx_type_id` (`type_id`),
    KEY `idx_code` (`code`),
    KEY `idx_first_letter` (`first_letter`),
    KEY `idx_status` (`status`),
    KEY `idx_is_recommend` (`is_recommend`),
    KEY `idx_is_hot` (`is_hot`),
    KEY `idx_is_new` (`is_new`),
    KEY `idx_download_count` (`download_count`),
    KEY `idx_view_count` (`view_count`),
    KEY `idx_published_at` (`published_at`),
    KEY `idx_category_status` (`category_id`, `status`),
    FULLTEXT KEY `ft_title_desc` (`title`, `description`)
) ENGINE=InnoDB COMMENT='下载对象主表';


-- 4.3 软件类型表
CREATE TABLE `software_type` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `name`          VARCHAR(100)    NOT NULL COMMENT '类型名称(如:系统工具/编程开发/游戏娱乐)',
    `code`          VARCHAR(50)     NOT NULL DEFAULT '' COMMENT '类型标识',
    `sort`          INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '排序',
    `status`        TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '状态',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`),
    KEY `idx_sort` (`sort`)
) ENGINE=InnoDB COMMENT='软件类型表';


-- 4.4 标签表
CREATE TABLE `tag` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `name`          VARCHAR(50)     NOT NULL COMMENT '标签名称',
    `count`         INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '关联软件数(冗余)',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_name` (`name`),
    KEY `idx_count` (`count`)
) ENGINE=InnoDB COMMENT='标签表';


-- 4.5 软件-标签关联表
CREATE TABLE `software_tag` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `software_id`   BIGINT UNSIGNED NOT NULL COMMENT '软件ID',
    `tag_id`        BIGINT UNSIGNED NOT NULL COMMENT '标签ID',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_software_tag` (`software_id`, `tag_id`),
    KEY `idx_tag_id` (`tag_id`)
) ENGINE=InnoDB COMMENT='软件-标签关联表';


-- 4.6 附件文件表
CREATE TABLE `attachment` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `software_id`   BIGINT UNSIGNED NOT NULL COMMENT '所属软件ID',
    `name`          VARCHAR(200)    NOT NULL COMMENT '附件名称',
    `file_path`     VARCHAR(500)    NOT NULL COMMENT '文件路径(相对路径)',
    `file_url`      VARCHAR(500)    NOT NULL DEFAULT '' COMMENT '文件URL(CDN地址)',
    `file_size`     BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '文件大小(字节)',
    `file_md5`      VARCHAR(32)     NOT NULL DEFAULT '' COMMENT '文件MD5',
    `file_ext`      VARCHAR(20)     NOT NULL DEFAULT '' COMMENT '文件扩展名',
    `mime_type`     VARCHAR(100)    NOT NULL DEFAULT '' COMMENT 'MIME类型',
    `download_count` INT UNSIGNED   NOT NULL DEFAULT 0 COMMENT '该附件被下载次数',
    `is_external`   TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '是否外部链接(1是 0否)',
    `sort`          INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '排序',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '上传时间',
    PRIMARY KEY (`id`),
    KEY `idx_software_id` (`software_id`),
    KEY `idx_file_md5` (`file_md5`)
) ENGINE=InnoDB COMMENT='附件文件表';


-- 4.7 专题表
CREATE TABLE `topic` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `name`          VARCHAR(200)    NOT NULL COMMENT '专题名称',
    `code`          VARCHAR(50)     NOT NULL DEFAULT '' COMMENT '专题标识(URL用)',
    `description`   TEXT            NULL     COMMENT '专题描述',
    `cover`         VARCHAR(500)    NOT NULL DEFAULT '' COMMENT '专题封面图',
    `sort`          INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '排序',
    `status`        TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '状态',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB COMMENT='专题表';


-- 4.8 专题-软件关联表
CREATE TABLE `topic_software` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `topic_id`      BIGINT UNSIGNED NOT NULL COMMENT '专题ID',
    `software_id`   BIGINT UNSIGNED NOT NULL COMMENT '软件ID',
    `sort`          INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '排序',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_topic_software` (`topic_id`, `software_id`),
    KEY `idx_software_id` (`software_id`)
) ENGINE=InnoDB COMMENT='专题-软件关联表';


-- ================================================================
-- 五、下载记录与统计模块
-- ================================================================

-- 5.1 下载记录表（大表，建议按月分表）
CREATE TABLE `download_log` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID(0为匿名)',
    `software_id`   BIGINT UNSIGNED NOT NULL COMMENT '软件ID',
    `attachment_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '附件ID',
    `ip`            VARCHAR(50)     NOT NULL DEFAULT '' COMMENT '下载IP',
    `user_agent`    VARCHAR(500)    NOT NULL DEFAULT '' COMMENT 'UserAgent',
    `points_cost`   INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '本次扣点数',
    `points_balance` INT            NOT NULL DEFAULT 0 COMMENT '扣费后剩余点数',
    `deducted`      TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '是否已扣费(1是 0否-重复下载免扣)',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '下载时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_software_id` (`software_id`),
    KEY `idx_ip` (`ip`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_software_time` (`software_id`, `created_at`)
) ENGINE=InnoDB COMMENT='下载记录表(建议按月分表)';


-- 5.2 下载统计表（每日汇总）
CREATE TABLE `download_stat` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `software_id`   BIGINT UNSIGNED NOT NULL COMMENT '软件ID',
    `stat_date`     DATE            NOT NULL COMMENT '统计日期',
    `download_count` INT UNSIGNED   NOT NULL DEFAULT 0 COMMENT '当日下载量',
    `unique_ip_count` INT UNSIGNED  NOT NULL DEFAULT 0 COMMENT '独立IP数',
    `points_total`  INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '当日总扣点数',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_software_date` (`software_id`, `stat_date`),
    KEY `idx_stat_date` (`stat_date`)
) ENGINE=InnoDB COMMENT='下载统计表(每日汇总)';


-- ================================================================
-- 六、评论与反馈模块
-- ================================================================

-- 6.1 评论表
CREATE TABLE `comment` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `software_id`   BIGINT UNSIGNED NOT NULL COMMENT '软件ID',
    `user_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID(0匿名)',
    `parent_id`     BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父评论ID(0为一级)',
    `reply_to_id`   BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '回复目标评论ID',
    `content`       TEXT            NOT NULL COMMENT '评论内容',
    `rating`        TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '评分(1-5,0表示仅评论)',
    `ip`            VARCHAR(50)     NOT NULL DEFAULT '' COMMENT '评论IP',
    `status`        TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '状态(0待审核 1通过 2拒绝)',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '评论时间',
    PRIMARY KEY (`id`),
    KEY `idx_software_id` (`software_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_parent_id` (`parent_id`),
    KEY `idx_status` (`status`),
    KEY `idx_software_status` (`software_id`, `status`)
) ENGINE=InnoDB COMMENT='评论表';


-- 6.2 错误报告表
CREATE TABLE `error_report` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `software_id`   BIGINT UNSIGNED NOT NULL COMMENT '软件ID',
    `user_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '报告人ID',
    `report_type`   VARCHAR(30)     NOT NULL DEFAULT '' COMMENT '报告类型(download_error=下载错误/link_dead=链接失效/content_error=内容错误/other=其他)',
    `content`       TEXT            NOT NULL COMMENT '报告内容',
    `ip`            VARCHAR(50)     NOT NULL DEFAULT '' COMMENT '报告IP',
    `status`        TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '状态(0待处理 1已处理 2忽略)',
    `handle_result` TEXT            NULL     COMMENT '处理结果',
    `handle_admin_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理管理员ID',
    `handled_at`    DATETIME        NULL     COMMENT '处理时间',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '报告时间',
    PRIMARY KEY (`id`),
    KEY `idx_software_id` (`software_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB COMMENT='错误报告表';


-- ================================================================
-- 七、广告管理模块
-- ================================================================

-- 7.1 广告位表
CREATE TABLE `ad_place` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `name`          VARCHAR(100)    NOT NULL COMMENT '广告位名称',
    `code`          VARCHAR(50)     NOT NULL COMMENT '广告位标识(首页横幅/侧边栏/弹窗等)',
    `width`         INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '建议宽度(px)',
    `height`        INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '建议高度(px)',
    `description`   VARCHAR(500)    NOT NULL DEFAULT '' COMMENT '广告位说明',
    `status`        TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '状态',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB COMMENT='广告位表';


-- 7.2 广告内容表
CREATE TABLE `ad_record` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `place_id`      BIGINT UNSIGNED NOT NULL COMMENT '所属广告位ID',
    `title`         VARCHAR(200)    NOT NULL COMMENT '广告标题',
    `type`          VARCHAR(30)     NOT NULL DEFAULT 'image' COMMENT '广告类型(image/flash/text/html/popup)',
    `show_type`     VARCHAR(30)     NOT NULL DEFAULT 'normal' COMMENT '展示效果(normal=普通/float_full=满屏浮动/float_updown=上下浮动/float_left=左边浮动/float_right=右边浮动/fade=全屏渐隐/movable=可移动对话框/couplet=对联)',
    `content`       TEXT            NULL     COMMENT '广告内容(文字/HTML代码)',
    `image_url`     VARCHAR(500)    NOT NULL DEFAULT '' COMMENT '广告图片URL',
    `link_url`      VARCHAR(500)    NOT NULL DEFAULT '' COMMENT '广告链接URL',
    `width`         INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '宽度',
    `height`        INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '高度',
    `click_count`   INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '点击次数',
    `start_time`    DATETIME        NULL     COMMENT '投放开始时间',
    `end_time`      DATETIME        NULL     COMMENT '投放结束时间',
    `sort`          INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '排序',
    `status`        TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '状态(1启用 0停用)',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_place_id` (`place_id`),
    KEY `idx_type` (`type`),
    KEY `idx_status_time` (`status`, `start_time`, `end_time`)
) ENGINE=InnoDB COMMENT='广告内容表';


-- 7.3 广告点击统计表
CREATE TABLE `ad_click_log` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `ad_id`         BIGINT UNSIGNED NOT NULL COMMENT '广告ID',
    `ip`            VARCHAR(50)     NOT NULL DEFAULT '' COMMENT '点击IP',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '点击时间',
    PRIMARY KEY (`id`),
    KEY `idx_ad_id` (`ad_id`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_ad_ip_time` (`ad_id`, `ip`, `created_at`)
) ENGINE=InnoDB COMMENT='广告点击统计表';


-- ================================================================
-- 八、备份与日志模块
-- ================================================================

-- 8.1 备份记录表
CREATE TABLE `backup_record` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `group_no`      INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '备份分组号',
    `total_groups`  INT UNSIGNED    NOT NULL DEFAULT 1 COMMENT '总分包数',
    `file_name`     VARCHAR(200)    NOT NULL COMMENT '备份文件名',
    `file_path`     VARCHAR(500)    NOT NULL COMMENT '备份文件路径',
    `file_size`     BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '文件大小(字节)',
    `tables_json`   JSON            NULL     COMMENT '本次备份的表列表',
    `backup_type`   VARCHAR(30)     NOT NULL DEFAULT 'manual' COMMENT '备份类型(manual=手动/auto=自动)',
    `status`        TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '状态(1正常 0异常)',
    `created_by`    BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建管理员ID',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '备份时间',
    PRIMARY KEY (`id`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_backup_type` (`backup_type`)
) ENGINE=InnoDB COMMENT='备份记录表';


-- 8.2 操作日志表（大表，建议按月分表）
CREATE TABLE `operation_log` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `admin_id`      BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '管理员ID',
    `username`      VARCHAR(50)     NOT NULL DEFAULT '' COMMENT '管理员用户名(冗余)',
    `module`        VARCHAR(50)     NOT NULL DEFAULT '' COMMENT '操作模块(software/category/user/ad等)',
    `action`        VARCHAR(50)     NOT NULL COMMENT '操作动作(create/update/delete/login/logout等)',
    `target_id`     BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作对象ID',
    `content`       TEXT            NULL     COMMENT '操作内容/详情(JSON)',
    `ip`            VARCHAR(50)     NOT NULL DEFAULT '' COMMENT '操作IP',
    `user_agent`    VARCHAR(500)    NOT NULL DEFAULT '' COMMENT 'UserAgent',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '操作时间',
    PRIMARY KEY (`id`),
    KEY `idx_admin_id` (`admin_id`),
    KEY `idx_module_action` (`module`, `action`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB COMMENT='操作日志表(建议按月分表)';


-- 8.3 用户登录日志表
CREATE TABLE `user_login_log` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id`       BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `username`      VARCHAR(50)     NOT NULL DEFAULT '' COMMENT '用户名(冗余)',
    `ip`            VARCHAR(50)     NOT NULL DEFAULT '' COMMENT '登录IP',
    `user_agent`    VARCHAR(500)    NOT NULL DEFAULT '' COMMENT 'UserAgent',
    `status`        TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '状态(1成功 0失败)',
    `fail_reason`   VARCHAR(200)    NOT NULL DEFAULT '' COMMENT '失败原因',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '登录时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_ip` (`ip`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB COMMENT='用户登录日志表';


-- ================================================================
-- 九、其他扩展模块
-- ================================================================

-- 9.1 自定义页面表
CREATE TABLE `custom_page` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `title`         VARCHAR(200)    NOT NULL COMMENT '页面标题',
    `code`          VARCHAR(50)     NOT NULL COMMENT '页面标识(URL伪静态)',
    `content`       LONGTEXT        NULL     COMMENT '页面内容(支持模板标签)',
    `seo_title`     VARCHAR(200)    NOT NULL DEFAULT '' COMMENT 'SEO标题',
    `seo_keywords`  VARCHAR(500)    NOT NULL DEFAULT '' COMMENT 'SEO关键词',
    `seo_description` TEXT          NULL     COMMENT 'SEO描述',
    `template`      VARCHAR(100)    NOT NULL DEFAULT '' COMMENT '自定义模板',
    `status`        TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '状态',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB COMMENT='自定义页面表';


-- 9.2 自定义列表表
CREATE TABLE `custom_list` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `title`         VARCHAR(200)    NOT NULL COMMENT '列表标题',
    `code`          VARCHAR(50)     NOT NULL COMMENT '列表标识(URL伪静态)',
    `category_ids`  JSON            NULL     COMMENT '包含的分类ID列表',
    `type_ids`      JSON            NULL     COMMENT '包含的软件类型ID列表',
    `order_by`      VARCHAR(50)     NOT NULL DEFAULT 'download_count' COMMENT '排序字段',
    `order_direction` VARCHAR(10)   NOT NULL DEFAULT 'desc' COMMENT '排序方向',
    `limit_count`   INT UNSIGNED    NOT NULL DEFAULT 20 COMMENT '每页数量',
    `template`      VARCHAR(100)    NOT NULL DEFAULT '' COMMENT '自定义模板',
    `seo_title`     VARCHAR(200)    NOT NULL DEFAULT '' COMMENT 'SEO标题',
    `seo_keywords`  VARCHAR(500)    NOT NULL DEFAULT '' COMMENT 'SEO关键词',
    `seo_description` TEXT          NULL     COMMENT 'SEO描述',
    `status`        TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '状态',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB COMMENT='自定义列表表';


-- 9.3 模板变量表
CREATE TABLE `template_var` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `name`          VARCHAR(50)     NOT NULL COMMENT '变量名(标签名)',
    `value`         TEXT            NOT NULL COMMENT '变量值(支持HTML/模板标签)',
    `description`   VARCHAR(500)    NOT NULL DEFAULT '' COMMENT '变量说明',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB COMMENT='模板变量表';


-- 9.4 投票主题表
CREATE TABLE `vote` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `title`         VARCHAR(200)    NOT NULL COMMENT '投票标题',
    `type`          VARCHAR(20)     NOT NULL DEFAULT 'single' COMMENT '投票类型(single=单选/multiple=多选)',
    `options`       JSON            NOT NULL COMMENT '投票选项(JSON数组,格式:[{"name":"选项A","count":10},...])',
    `total_count`   INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '总投票数',
    `start_time`    DATETIME        NULL     COMMENT '开始时间',
    `end_time`      DATETIME        NULL     COMMENT '结束时间',
    `is_multi_ip`   TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '是否限制重复IP投票(1是 0否)',
    `status`        TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '状态',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB COMMENT='投票主题表';


-- 9.5 投票记录表
CREATE TABLE `vote_log` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `vote_id`       BIGINT UNSIGNED NOT NULL COMMENT '投票ID',
    `user_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `option_index`  TINYINT UNSIGNED NOT NULL COMMENT '选项序号(从0开始)',
    `ip`            VARCHAR(50)     NOT NULL DEFAULT '' COMMENT '投票IP',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '投票时间',
    PRIMARY KEY (`id`),
    KEY `idx_vote_id` (`vote_id`),
    KEY `idx_ip` (`ip`),
    KEY `idx_vote_ip_user` (`vote_id`, `ip`, `user_id`)
) ENGINE=InnoDB COMMENT='投票记录表';


-- 9.6 友情链接表
CREATE TABLE `friend_link` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `name`          VARCHAR(100)    NOT NULL COMMENT '链接名称',
    `url`           VARCHAR(500)    NOT NULL COMMENT '链接URL',
    `logo`          VARCHAR(500)    NOT NULL DEFAULT '' COMMENT 'Logo图片URL(空为文字链接)',
    `description`   VARCHAR(500)    NOT NULL DEFAULT '' COMMENT '描述',
    `sort`          INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '排序',
    `status`        TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '状态(1显示 0隐藏)',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '添加时间',
    PRIMARY KEY (`id`),
    KEY `idx_sort` (`sort`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB COMMENT='友情链接表';


-- 9.7 公告表
CREATE TABLE `announcement` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `title`         VARCHAR(200)    NOT NULL COMMENT '公告标题',
    `content`       TEXT            NOT NULL COMMENT '公告内容',
    `type`          VARCHAR(30)     NOT NULL DEFAULT 'normal' COMMENT '公告类型(normal=普通/top=置顶/scroll=滚动/popup=弹窗)',
    `is_sticky`     TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '是否置顶(1是 0否)',
    `sort`          INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '排序',
    `status`        TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '状态(1显示 0隐藏)',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '发布时间',
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_status_type` (`status`, `type`),
    KEY `idx_is_sticky_sort` (`is_sticky`, `sort`)
) ENGINE=InnoDB COMMENT='公告表';


-- 9.8 万能接口配置表
CREATE TABLE `integration_config` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `name`          VARCHAR(100)    NOT NULL COMMENT '接口名称',
    `db_host`       VARCHAR(100)    NOT NULL COMMENT '数据库主机',
    `db_port`       INT UNSIGNED    NOT NULL DEFAULT 3306 COMMENT '数据库端口',
    `db_name`       VARCHAR(100)    NOT NULL COMMENT '数据库名',
    `db_user`       VARCHAR(100)    NOT NULL COMMENT '数据库用户',
    `db_password`   VARCHAR(255)    NOT NULL COMMENT '数据库密码(加密存储)',
    `db_charset`    VARCHAR(20)     NOT NULL DEFAULT 'utf8mb4' COMMENT '数据库编码',
    `user_table`    VARCHAR(100)    NOT NULL COMMENT '用户表名',
    `user_id_field` VARCHAR(50)     NOT NULL DEFAULT 'id' COMMENT '用户ID字段',
    `username_field` VARCHAR(50)    NOT NULL DEFAULT 'username' COMMENT '用户名字段',
    `password_field` VARCHAR(50)    NOT NULL DEFAULT 'password' COMMENT '密码字段',
    `group_field`   VARCHAR(50)     NOT NULL DEFAULT '' COMMENT '会员组字段',
    `points_field`  VARCHAR(50)     NOT NULL DEFAULT '' COMMENT '点数字段',
    `password_hash` VARCHAR(30)     NOT NULL DEFAULT 'md5' COMMENT '密码加密方式(md5/sha1/bcrypt)',
    `status`        TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '状态(1启用 0禁用)',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB COMMENT='万能接口配置表(对接第三方MySQL用户系统)';


-- ================================================================
-- 十、初始化数据
-- ================================================================

-- 插入默认会员组
INSERT INTO `user_group` (`name`, `code`, `level`, `is_default`, `max_favorites`, `max_download_day`, `description`) VALUES
('普通会员', 'normal', 1, 1, 100, 10, '注册即享，每日下载限额10次'),
('VIP会员', 'vip', 2, 0, 500, 50, 'VIP特权，下载8折优惠'),
('SVIP会员', 'svip', 3, 0, 9999, 0, 'SVIP无限下载，下载5折优惠');

-- 插入默认角色和超级管理员
INSERT INTO `role` (`name`, `code`, `permissions`, `description`) VALUES
('超级管理员', 'super_admin', '["*"]', '拥有所有权限'),
('内容编辑', 'editor', '["software:list","software:create","software:update","category:list","category:create"]', '内容编辑权限');

-- 超级管理员密码: admin123 (bcrypt)
INSERT INTO `admin_user` (`username`, `password`, `nickname`, `role_id`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '超级管理员', 1);

-- 插入默认模板变量
INSERT INTO `template_var` (`name`, `value`, `description`) VALUES
('site_name', '帝国下载站', '站点名称'),
('site_keywords', '软件下载,免费软件,VIP下载', '站点关键词'),
('site_description', '帝国下载系统 - 专业软件资源下载平台', '站点描述'),
('copyright', '© 2026 帝国下载站 All Rights Reserved', '版权信息');

-- 插入默认广告位
INSERT INTO `ad_place` (`name`, `code`, `width`, `height`, `description`) VALUES
('首页横幅', 'home_banner', 1200, 280, '首页顶部横幅广告位'),
('首页侧边栏', 'home_sidebar', 300, 250, '首页右侧边栏广告位'),
('下载页横幅', 'download_banner', 800, 100, '下载详情页顶部横幅'),
('全站弹窗', 'site_popup', 600, 400, '全站弹窗广告位');


-- ================================================================
-- 附录：表关系总览（ER简化版）
-- ================================================================
/*
┌───────────────────┐      ┌───────────────────┐      ┌───────────────────┐
│    admin_user     │──────│       role        │      │   system_config   │
│  (后台管理员)      │ M:1  │     (角色RBAC)     │      │   (系统配置KV)     │
└───────────────────┘      └───────────────────┘      └───────────────────┘

┌───────────────────┐      ┌───────────────────┐      ┌───────────────────┐
│    user_group     │◄─────│       user        │──────│  user_points_log  │
│    (会员组)        │ 1:M  │     (会员用户)      │ 1:M  │  (点数变动日志)    │
└───────────────────┘      └────────┬──────────┘      └───────────────────┘
                                    │
                    ┌───────────────┼───────────────┐
                    │ 1:M           │ 1:M           │ 1:M
                    ▼               ▼               ▼
           ┌───────────┐   ┌───────────────┐   ┌───────────────┐
           │user_recharge│  │ user_favorite │   │ user_login_log│
           │ (充值记录)   │  │  (收藏夹)     │   │ (登录日志)     │
           └───────────┘   └───────┬───────┘   └───────────────┘
                                   │ M:1
                                   ▼
┌───────────────────┐      ┌───────────────────┐      ┌───────────────────┐
│     category      │◄─────│     software      │──────│   software_type   │
│   (无限级分类)     │ 1:M  │   (下载对象主表)   │ M:1  │   (软件类型)      │
│   parent_id递归    │      └────────┬──────────┘      └───────────────────┘
└───────────────────┘               │
                    ┌───────────────┼───────────────┬───────────────┐
                    │ 1:M           │ M:N           │ 1:M           │ 1:M
                    ▼               ▼               ▼               ▼
            ┌───────────┐   ┌───────────────┐   ┌───────────┐   ┌───────────┐
            │ attachment │   │ software_tag  │   │  download │   │  comment  │
            │ (附件文件)  │   │ (软件-标签)    │   │   _log    │   │  (评论)   │
            └───────────┘   └───────┬───────┘   │(下载记录)  │   └───────────┘
                                    │ M:1       └───────────┘
                                    ▼
                            ┌───────────────┐
                            │      tag      │
                            │   (标签表)    │
                            └───────────────┘

┌───────────────────┐      ┌───────────────────┐
│     ad_place      │◄─────│     ad_record     │──────▶ ad_click_log
│    (广告位)        │ 1:M  │    (广告内容)      │ 1:M    (广告点击)
└───────────────────┘      └───────────────────┘

┌───────────────────┐      ┌───────────────────┐
│       topic       │◄─────│  topic_software   │──────▶ software
│     (专题)         │ M:N  │  (专题-软件关联)   │ M:N    (下载对象)
└───────────────────┘      └───────────────────┘

其他独立表:
  - point_card_batch / point_card    (点卡批次/明细)
  - custom_page / custom_list         (自定义页面/列表)
  - template_var                      (模板变量)
  - vote / vote_log                   (投票/记录)
  - friend_link                       (友情链接)
  - announcement                      (公告)
  - integration_config                (万能接口配置)
  - backup_record                     (备份记录)
  - operation_log                     (操作日志)
  - download_stat                     (下载统计)
  - error_report                      (错误报告)
*/
