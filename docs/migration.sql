-- ============================================================
-- 帝国下载系统 数据迁移脚本（老系统 → 新系统）
-- 版本: 2.5 → 3.0 (ThinkPHP 8 重构版)
-- 执行方式: mysql -u root empiredown < migrate.sql
-- ============================================================

-- 1. 用户表迁移（empire_user → users）
INSERT INTO users (id, username, password, salt, email, group_id, points, expire_time, status, created_at)
SELECT 
    userid, username, password, salt, email, groupid, 
    COALESCE(userfen, 0), 
    IF(userdate > 0, FROM_UNIXTIME(userdate), NULL),
    checked, FROM_UNIXTIME(registertime)
FROM empire_user
ON DUPLICATE KEY UPDATE 
    username = VALUES(username),
    points = VALUES(points);

-- 2. 会员组表迁移（empire_group → user_groups）
INSERT INTO user_groups (id, name, level, points, max_download_day, created_at)
SELECT groupid, groupname, level, COALESCE(fen, 0), maxdown, NOW()
FROM empire_group
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- 3. 软件分类迁移（empire_class → categories）
INSERT INTO categories (id, name, parent_id, sort_order, created_at)
SELECT classid, classname, bclassid, myorder, NOW()
FROM empire_class
ORDER BY classid;

-- 4. 软件数据迁移（empire_download → software）
INSERT INTO software (id, category_id, name, summary, description, download_url, points, status, created_at)
SELECT 
    id, classid, title, smalltext, newstext, 
    downloadurl, COALESCE(fen, 0), checked, FROM_UNIXTIME(newstime)
FROM empire_download
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- 5. 充值记录迁移（empire_buy → user_recharge）
INSERT INTO user_recharge (id, user_id, amount, points, payment_channel, status, paid_at, created_at)
SELECT 
    bid, userid, COALESCE(money, 0), COALESCE(fen, 0),
    CASE paytype WHEN 'alipay' THEN 'alipay' WHEN 'wxpay' THEN 'wechat' ELSE 'manual' END,
    CASE checked WHEN 1 THEN 1 ELSE 0 END,
    FROM_UNIXTIME(buytime), FROM_UNIXTIME(buytime)
FROM empire_buy
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- 6. 下载记录迁移（empire_downrecord → download_logs）
INSERT INTO download_logs (id, user_id, software_id, ip, created_at)
SELECT id, userid, downid, downip, FROM_UNIXTIME(downtime)
FROM empire_downrecord;

-- ============================================================
-- 迁移后数据修正
-- ============================================================

-- 更新自增ID（避免后续插入冲突）
SELECT MAX(id)+1 INTO @next_id FROM users;
SET @sql = CONCAT('ALTER TABLE users AUTO_INCREMENT = ', @next_id);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT MAX(id)+1 INTO @next_id FROM software;
SET @sql = CONCAT('ALTER TABLE software AUTO_INCREMENT = ', @next_id);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT MAX(id)+1 INTO @next_id FROM user_recharge;
SET @sql = CONCAT('ALTER TABLE user_recharge AUTO_INCREMENT = ', @next_id);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 迁移验证查询
SELECT 'users' AS tbl, COUNT(*) AS cnt FROM users
UNION ALL SELECT 'user_groups', COUNT(*) FROM user_groups
UNION ALL SELECT 'categories', COUNT(*) FROM categories
UNION ALL SELECT 'software', COUNT(*) FROM software
UNION ALL SELECT 'user_recharge', COUNT(*) FROM user_recharge
UNION ALL SELECT 'download_logs', COUNT(*) FROM download_logs;
