-- 帝国下载系统 完整数据库 Schema（CI 自动生成）
-- 生成时间: 2026-05-06 14:24:13

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `ad_click_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `ad_id` bigint unsigned NOT NULL COMMENT '广告ID',
  `ip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '点击IP',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '点击时间',
  PRIMARY KEY (`id`),
  KEY `idx_ad_id` (`ad_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_ad_ip_time` (`ad_id`,`ip`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='广告点击统计表';

CREATE TABLE `ad_place` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告位名称',
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告位标识(首页横幅/侧边栏/弹窗等)',
  `width` int unsigned NOT NULL DEFAULT '0' COMMENT '建议宽度(px)',
  `height` int unsigned NOT NULL DEFAULT '0' COMMENT '建议高度(px)',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '广告位说明',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='广告位表';

CREATE TABLE `ad_record` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `place_id` bigint unsigned NOT NULL COMMENT '所属广告位ID',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告标题',
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'image' COMMENT '广告类型(image/flash/text/html/popup)',
  `show_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '展示效果(normal=普通/float_full=满屏浮动/float_updown=上下浮动/float_left=左边浮动/float_right=右边浮动/fade=全屏渐隐/movable=可移动对话框/couplet=对联)',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '广告内容(文字/HTML代码)',
  `image_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '广告图片URL',
  `link_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '广告链接URL',
  `width` int unsigned NOT NULL DEFAULT '0' COMMENT '宽度',
  `height` int unsigned NOT NULL DEFAULT '0' COMMENT '高度',
  `click_count` int unsigned NOT NULL DEFAULT '0' COMMENT '点击次数',
  `start_time` datetime DEFAULT NULL COMMENT '投放开始时间',
  `end_time` datetime DEFAULT NULL COMMENT '投放结束时间',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1启用 0停用)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_place_id` (`place_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status_time` (`status`,`start_time`,`end_time`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='广告内容表';

CREATE TABLE `admin_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户名',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '密码(bcrypt哈希)',
  `nickname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '昵称',
  `avatar` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '头像URL',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `mobile` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `role_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '角色ID',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1正常 0禁用)',
  `login_ip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `login_count` int unsigned NOT NULL DEFAULT '0' COMMENT '登录次数',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台管理员表';

CREATE TABLE `announcement` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '公告标题',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '公告内容',
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '公告类型(normal=普通/top=置顶/scroll=滚动/popup=弹窗)',
  `is_sticky` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否置顶(1是 0否)',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1显示 0隐藏)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '发布时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_status_type` (`status`,`type`),
  KEY `idx_is_sticky_sort` (`is_sticky`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公告表';

CREATE TABLE `attachment` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `software_id` bigint unsigned NOT NULL COMMENT '所属软件ID',
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '附件名称',
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件路径(相对路径)',
  `file_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件URL(CDN地址)',
  `file_size` bigint unsigned NOT NULL DEFAULT '0' COMMENT '文件大小(字节)',
  `file_md5` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件MD5',
  `file_ext` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件扩展名',
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'MIME类型',
  `download_count` int unsigned NOT NULL DEFAULT '0' COMMENT '该附件被下载次数',
  `is_external` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否外部链接(1是 0否)',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '上传时间',
  PRIMARY KEY (`id`),
  KEY `idx_software_id` (`software_id`),
  KEY `idx_file_md5` (`file_md5`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='附件文件表';

CREATE TABLE `backup_record` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `group_no` int unsigned NOT NULL DEFAULT '0' COMMENT '备份分组号',
  `total_groups` int unsigned NOT NULL DEFAULT '1' COMMENT '总分包数',
  `file_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '备份文件名',
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '备份文件路径',
  `file_size` bigint unsigned NOT NULL DEFAULT '0' COMMENT '文件大小(字节)',
  `tables_json` json DEFAULT NULL COMMENT '本次备份的表列表',
  `backup_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual' COMMENT '备份类型(manual=手动/auto=自动)',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1正常 0异常)',
  `created_by` bigint unsigned NOT NULL DEFAULT '0' COMMENT '创建管理员ID',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '备份时间',
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_backup_type` (`backup_type`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='备份记录表';

CREATE TABLE `category` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `parent_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '父分类ID(0为顶级)',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '分类名称',
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类标识(URL伪静态用)',
  `path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '层级路径(如:0-1-5-/逗号分隔符)',
  `level` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '层级深度',
  `icon` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类图标',
  `image` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类封面图',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '分类描述',
  `template_list` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '自定义列表页模板',
  `template_detail` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '自定义详情页模板',
  `seo_title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_keywords` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `seo_description` text COLLATE utf8mb4_unicode_ci COMMENT 'SEO描述',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1显示 0隐藏)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_path` (`path`),
  KEY `idx_code` (`code`),
  KEY `idx_status_sort` (`status`,`sort`),
  FULLTEXT KEY `ft_search` (`name`,`description`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='无限级分类表';

CREATE TABLE `comment` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `software_id` bigint unsigned NOT NULL COMMENT '软件ID',
  `user_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '用户ID(0匿名)',
  `parent_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '父评论ID(0为一级)',
  `reply_to_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '回复目标评论ID',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '评论内容',
  `rating` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '评分(1-5,0表示仅评论)',
  `ip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '评论IP',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态(0待审核 1通过 2拒绝)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '评论时间',
  PRIMARY KEY (`id`),
  KEY `idx_software_id` (`software_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_status` (`status`),
  KEY `idx_software_status` (`software_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='评论表';

CREATE TABLE `custom_list` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '列表标题',
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '列表标识(URL伪静态)',
  `category_ids` json DEFAULT NULL COMMENT '包含的分类ID列表',
  `type_ids` json DEFAULT NULL COMMENT '包含的软件类型ID列表',
  `order_by` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'download_count' COMMENT '排序字段',
  `order_direction` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'desc' COMMENT '排序方向',
  `limit_count` int unsigned NOT NULL DEFAULT '20' COMMENT '每页数量',
  `template` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '自定义模板',
  `seo_title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_keywords` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `seo_description` text COLLATE utf8mb4_unicode_ci COMMENT 'SEO描述',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='自定义列表表';

CREATE TABLE `custom_page` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '页面标题',
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '页面标识(URL伪静态)',
  `content` longtext COLLATE utf8mb4_unicode_ci COMMENT '页面内容(支持模板标签)',
  `seo_title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_keywords` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `seo_description` text COLLATE utf8mb4_unicode_ci COMMENT 'SEO描述',
  `template` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '自定义模板',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='自定义页面表';

CREATE TABLE `download_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '用户ID(0为匿名)',
  `software_id` bigint unsigned NOT NULL COMMENT '软件ID',
  `attachment_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '附件ID',
  `ip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '下载IP',
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'UserAgent',
  `points_cost` int unsigned NOT NULL DEFAULT '0' COMMENT '本次扣点数',
  `points_balance` int NOT NULL DEFAULT '0' COMMENT '扣费后剩余点数',
  `deducted` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已扣费(1是 0否-重复下载免扣)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '下载时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_software_id` (`software_id`),
  KEY `idx_ip` (`ip`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_software_time` (`software_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='下载记录表(建议按月分表)';

CREATE TABLE `download_stat` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `software_id` bigint unsigned NOT NULL COMMENT '软件ID',
  `stat_date` date NOT NULL COMMENT '统计日期',
  `download_count` int unsigned NOT NULL DEFAULT '0' COMMENT '当日下载量',
  `unique_ip_count` int unsigned NOT NULL DEFAULT '0' COMMENT '独立IP数',
  `points_total` int unsigned NOT NULL DEFAULT '0' COMMENT '当日总扣点数',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_software_date` (`software_id`,`stat_date`),
  KEY `idx_stat_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='下载统计表(每日汇总)';

CREATE TABLE `error_report` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `software_id` bigint unsigned NOT NULL COMMENT '软件ID',
  `user_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '报告人ID',
  `report_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '报告类型(download_error=下载错误/link_dead=链接失效/content_error=内容错误/other=其他)',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '报告内容',
  `ip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '报告IP',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态(0待处理 1已处理 2忽略)',
  `handle_result` text COLLATE utf8mb4_unicode_ci COMMENT '处理结果',
  `handle_admin_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '处理管理员ID',
  `handled_at` datetime DEFAULT NULL COMMENT '处理时间',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '报告时间',
  PRIMARY KEY (`id`),
  KEY `idx_software_id` (`software_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='错误报告表';

CREATE TABLE `friend_link` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '链接名称',
  `url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '链接URL',
  `logo` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Logo图片URL(空为文字链接)',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '描述',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1显示 0隐藏)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `idx_sort` (`sort`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='友情链接表';

CREATE TABLE `integration_config` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '接口名称',
  `db_host` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '数据库主机',
  `db_port` int unsigned NOT NULL DEFAULT '3306' COMMENT '数据库端口',
  `db_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '数据库名',
  `db_user` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '数据库用户',
  `db_password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '数据库密码(加密存储)',
  `db_charset` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'utf8mb4' COMMENT '数据库编码',
  `user_table` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户表名',
  `user_id_field` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'id' COMMENT '用户ID字段',
  `username_field` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'username' COMMENT '用户名字段',
  `password_field` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'password' COMMENT '密码字段',
  `group_field` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '会员组字段',
  `points_field` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '点数字段',
  `password_hash` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'md5' COMMENT '密码加密方式(md5/sha1/bcrypt)',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1启用 0禁用)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='万能接口配置表(对接第三方MySQL用户系统)';

CREATE TABLE `operation_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `admin_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '管理员用户名(冗余)',
  `module` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作模块(software/category/user/ad等)',
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '操作动作(create/update/delete/login/logout等)',
  `target_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '操作对象ID',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '操作内容/详情(JSON)',
  `ip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作IP',
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'UserAgent',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '操作时间',
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_module_action` (`module`,`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志表(建议按月分表)';

CREATE TABLE `pay_channels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `app_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mch_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `api_key` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `private_key` text COLLATE utf8mb4_unicode_ci,
  `public_key` text COLLATE utf8mb4_unicode_ci,
  `notify_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `return_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `remark` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='支付渠道配置表';

CREATE TABLE `point_card` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `batch_id` bigint unsigned NOT NULL COMMENT '批次ID',
  `card_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '卡号',
  `password` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '卡密',
  `points` int unsigned NOT NULL COMMENT '面额点数',
  `group_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '赠送会员组ID',
  `group_days` int unsigned NOT NULL DEFAULT '0' COMMENT '赠送会员天数',
  `expire_time` datetime NOT NULL COMMENT '过期时间',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态(0未使用 1已使用 2已过期)',
  `used_user_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '使用人ID',
  `used_time` datetime DEFAULT NULL COMMENT '使用时间',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '生成时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_card_no` (`card_no`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_status` (`status`),
  KEY `idx_used_user_id` (`used_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='点卡明细表';

CREATE TABLE `point_card_batch` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `batch_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '批次号',
  `total_count` int unsigned NOT NULL COMMENT '生成总张数',
  `used_count` int unsigned NOT NULL DEFAULT '0' COMMENT '已使用张数',
  `points` int unsigned NOT NULL COMMENT '每张卡面点数',
  `expire_days` int unsigned NOT NULL DEFAULT '365' COMMENT '有效期天数(从生成日起算)',
  `group_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '赠送会员组ID(0表示不赠送)',
  `group_days` int unsigned NOT NULL DEFAULT '0' COMMENT '赠送会员天数(0表示不赠送)',
  `created_by` bigint unsigned NOT NULL DEFAULT '0' COMMENT '创建管理员ID',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_batch_no` (`batch_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='点卡批次表';

CREATE TABLE `recharge_packages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `points` int unsigned NOT NULL DEFAULT '0',
  `bonus_points` int unsigned NOT NULL DEFAULT '0',
  `group_id` bigint unsigned NOT NULL DEFAULT '0',
  `group_days` int unsigned NOT NULL DEFAULT '0',
  `icon` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `is_hot` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status_sort` (`status`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='充值套餐表';

CREATE TABLE `role` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '角色名称',
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '角色标识(super_admin/editor/auditor)',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '角色描述',
  `permissions` json DEFAULT NULL COMMENT '权限JSON数组(如["software:list","software:create"])',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1正常 0禁用)',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色表(RBAC)';

CREATE TABLE `software` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '软件标题',
  `subtitle` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '副标题',
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '软件标识(URL伪静态)',
  `category_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '所属分类ID',
  `type_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '软件类型ID',
  `language` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '中文' COMMENT '软件语言(中文/英文/多语言)',
  `license` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '免费' COMMENT '授权方式(免费/共享/商业/开源)',
  `platform` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '运行平台(Windows/Linux/Mac/Android/iOS等)',
  `version` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '软件版本号',
  `file_size` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件大小(如:102.4MB)',
  `author` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '作者/开发商',
  `homepage` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '官方主页URL',
  `require_points` int unsigned NOT NULL DEFAULT '1' COMMENT '下载所需点数',
  `require_group` bigint unsigned NOT NULL DEFAULT '0' COMMENT '下载所需会员组ID(0不限制)',
  `deduct_interval` int unsigned NOT NULL DEFAULT '1440' COMMENT '重复下载扣费间隔(分钟,1440=24h)',
  `thumbnail` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '缩略图URL',
  `images` json DEFAULT NULL COMMENT '软件截图(JSON数组)',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '软件简介(短)',
  `content` longtext COLLATE utf8mb4_unicode_ci COMMENT '软件详细描述(富文本)',
  `changelog` text COLLATE utf8mb4_unicode_ci COMMENT '更新日志',
  `first_letter` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '首字母(大小写,用于字母导航)',
  `is_recommend` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否推荐(1是 0否)',
  `is_hot` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否热门(1是 0否)',
  `is_new` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否新品(1是 0否)',
  `download_count` int unsigned NOT NULL DEFAULT '0' COMMENT '总下载次数(冗余字段,定时同步)',
  `view_count` int unsigned NOT NULL DEFAULT '0' COMMENT '浏览次数',
  `comment_count` int unsigned NOT NULL DEFAULT '0' COMMENT '评论数(冗余)',
  `rating` decimal(2,1) NOT NULL DEFAULT '0.0' COMMENT '评分(0-5,冗余)',
  `rating_count` int unsigned NOT NULL DEFAULT '0' COMMENT '评分人数',
  `seo_title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_keywords` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `seo_description` text COLLATE utf8mb4_unicode_ci COMMENT 'SEO描述',
  `source` tinyint(1) NOT NULL DEFAULT '1' COMMENT '来源(1管理员发布 2用户投稿)',
  `submit_user_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '投稿用户ID',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(0待审核 1已发布 2退回 3草稿)',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `published_at` datetime DEFAULT NULL COMMENT '发布时间',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '软删除时间',
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
  KEY `idx_category_status` (`category_id`,`status`),
  FULLTEXT KEY `ft_search` (`title`,`content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='下载对象主表';

CREATE TABLE `software_tag` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `software_id` bigint unsigned NOT NULL COMMENT '软件ID',
  `tag_id` bigint unsigned NOT NULL COMMENT '标签ID',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_software_tag` (`software_id`,`tag_id`),
  KEY `idx_tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='软件-标签关联表';

CREATE TABLE `software_type` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '类型名称(如:系统工具/编程开发/游戏娱乐)',
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '类型标识',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='软件类型表';

CREATE TABLE `system_config` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `group` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'base' COMMENT '配置分组(base/member/payment/download/email/upload/security)',
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '配置键名',
  `value` text COLLATE utf8mb4_unicode_ci COMMENT '配置值',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string' COMMENT '值类型(string/int/float/bool/json/array)',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置标题（中文说明）',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置描述',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序(越小越前)',
  `is_system` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否系统级配置(1是 0否,系统级不可删除)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_group_key` (`group`,`key`),
  KEY `idx_group` (`group`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表(键值对)';

CREATE TABLE `tag` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '标签名称',
  `count` int unsigned NOT NULL DEFAULT '0' COMMENT '关联软件数(冗余)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  KEY `idx_count` (`count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='标签表';

CREATE TABLE `template_var` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '变量名(标签名)',
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '变量值(支持HTML/模板标签)',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '变量说明',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板变量表';

CREATE TABLE `topic` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '专题名称',
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '专题标识(URL用)',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '专题描述',
  `cover` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '专题封面图',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='专题表';

CREATE TABLE `topic_software` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `topic_id` bigint unsigned NOT NULL COMMENT '专题ID',
  `software_id` bigint unsigned NOT NULL COMMENT '软件ID',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_topic_software` (`topic_id`,`software_id`),
  KEY `idx_software_id` (`software_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='专题-软件关联表';

CREATE TABLE `user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户名',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '密码(bcrypt哈希)',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `mobile` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `nickname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '昵称',
  `avatar` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '头像URL',
  `group_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '会员组ID',
  `points` int NOT NULL DEFAULT '0' COMMENT '剩余点数(可正可负,0为无点数)',
  `expire_time` datetime DEFAULT NULL COMMENT '会员有效期(到期自动降级为默认组)',
  `total_recharge` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '累计充值金额',
  `total_download` int unsigned NOT NULL DEFAULT '0' COMMENT '累计下载次数',
  `today_download` int unsigned NOT NULL DEFAULT '0' COMMENT '今日下载次数(每日0点重置)',
  `register_ip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '注册IP',
  `login_ip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `openid_wechat` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '微信OpenID',
  `openid_qq` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'QQ OpenID',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1正常 0禁用 -1删除)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '注册时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_group_id` (`group_id`),
  KEY `idx_status` (`status`),
  KEY `idx_expire_time` (`expire_time`),
  KEY `idx_openid_wechat` (`openid_wechat`),
  KEY `idx_openid_qq` (`openid_qq`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员用户表';

CREATE TABLE `user_favorite` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` bigint unsigned NOT NULL COMMENT '会员ID',
  `software_id` bigint unsigned NOT NULL COMMENT '下载对象ID',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '收藏时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_software` (`user_id`,`software_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_software_id` (`software_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户收藏夹表';

CREATE TABLE `user_group` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '会员组名称(如:普通会员/VIP/SVIP)',
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '会员组标识(normal/vip/svip)',
  `level` int unsigned NOT NULL DEFAULT '0' COMMENT '会员级别(数字越大级别越高)',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否默认会员组(1是 0否)',
  `price_month` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '包月价格',
  `price_quarter` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '包季价格',
  `price_year` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '包年价格',
  `max_favorites` int unsigned NOT NULL DEFAULT '100' COMMENT '最大收藏夹数',
  `max_download_day` int unsigned NOT NULL DEFAULT '10' COMMENT '每日最大下载数(0不限)',
  `discount` decimal(3,2) NOT NULL DEFAULT '1.00' COMMENT '下载点数折扣(1.00=原价)',
  `icon` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '会员组图标URL',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '会员组描述/特权说明',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1启用 0禁用)',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_level` (`level`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员组表';

CREATE TABLE `user_login_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户名(冗余)',
  `ip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '登录IP',
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'UserAgent',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1成功 0失败)',
  `fail_reason` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '失败原因',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '登录时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_ip` (`ip`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户登录日志表';

CREATE TABLE `user_points_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` bigint unsigned NOT NULL COMMENT '会员ID',
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '变动类型(recharge=充值/download=下载扣费/refund=退款/admin_adjust=管理员调整/gift=赠送)',
  `amount` int NOT NULL COMMENT '变动点数(正=增加,负=减少)',
  `balance` int NOT NULL DEFAULT '0' COMMENT '变动后余额',
  `relation_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联业务ID(充值记录ID/下载记录ID等)',
  `remark` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '记录时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_user_type_time` (`user_id`,`type`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='点数变动日志表';

CREATE TABLE `user_recharge` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` bigint unsigned NOT NULL COMMENT '会员ID',
  `order_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '充值订单号(唯一)',
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '充值类型(point_card=点卡/online_bank=网银/alipay=支付宝/wechat=微信/admin=管理员手动)',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '充值金额',
  `points` int unsigned NOT NULL DEFAULT '0' COMMENT '获得点数',
  `group_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '获得会员组ID',
  `group_days` int unsigned NOT NULL DEFAULT '0' COMMENT '获得会员天数',
  `payment_channel` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '支付渠道(具体网关)',
  `trade_no` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '第三方交易流水号',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态(0待支付 1已支付 2已取消 3已退款)',
  `paid_at` datetime DEFAULT NULL COMMENT '支付时间',
  `remark` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `ip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '充值IP',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_trade_no` (`trade_no`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_user_status_time` (`user_id`,`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='充值记录表';

CREATE TABLE `vote` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '投票标题',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'single' COMMENT '投票类型(single=单选/multiple=多选)',
  `options` json NOT NULL COMMENT '投票选项(JSON数组,格式:[{"name":"选项A","count":10},...])',
  `total_count` int unsigned NOT NULL DEFAULT '0' COMMENT '总投票数',
  `start_time` datetime DEFAULT NULL COMMENT '开始时间',
  `end_time` datetime DEFAULT NULL COMMENT '结束时间',
  `is_multi_ip` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否限制重复IP投票(1是 0否)',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='投票主题表';

CREATE TABLE `vote_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `vote_id` bigint unsigned NOT NULL COMMENT '投票ID',
  `user_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `option_index` tinyint unsigned NOT NULL COMMENT '选项序号(从0开始)',
  `ip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '投票IP',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '投票时间',
  PRIMARY KEY (`id`),
  KEY `idx_vote_id` (`vote_id`),
  KEY `idx_ip` (`ip`),
  KEY `idx_vote_ip_user` (`vote_id`,`ip`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='投票记录表';

-- ============ 种子数据 ============

INSERT INTO `role` (`name`, `code`, `description`, `permissions`, `status`, `sort`) VALUES
('超级管理员', 'super_admin', '拥有所有权限', '["*"]', 1, 0),
('内容编辑', 'editor', '内容编辑权限', '["software:list","software:create","software:update","category:list"]', 1, 0);

INSERT INTO `admin_user` (`username`, `password`, `nickname`, `role_id`, `status`) VALUES
('admin', '$2y$10$4s3B2VekbdZ2TR244NTwPO/yE6k62K6B799FMmxIwzPJHEju7NrYa', '系统管理员', 1, 1);

INSERT INTO `user` (`username`, `password`, `nickname`, `group_id`, `points`, `status`) VALUES
('testuser', '$2y$10$gj/l4DrwFMU/3aOrYytBLOthwVPl/JoUFlpUTLkajyG2luE49Hh0C', '测试用户', 1, 100, 1);

INSERT INTO `user_group` (`name`, `code`, `level`, `max_download_day`) VALUES
('普通会员', 'normal', 1, 10);

INSERT INTO `pay_channels` (`name`, `code`, `app_id`, `mch_id`, `api_key`, `status`, `sort_order`, `remark`) VALUES
('支付宝', 'alipay', 'app_test_2026010100000001', 'mch_test_alipay_001', 'test_alipay_key_32chars_xxxxx', 1, 1, '支付宝支付渠道(测试)'),
('微信支付', 'wechat', 'wx_test_2026010100000001', 'mch_test_wechat_001', 'test_wechat_key_32chars_xxxxx', 1, 2, '微信支付渠道(测试)');

SET FOREIGN_KEY_CHECKS = 1;
