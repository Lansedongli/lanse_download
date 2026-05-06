-- ================================================================
-- 全站搜索 FULLTEXT 索引迁移
-- 为 software 和 category 表添加 FULLTEXT 索引
-- 适用于 MySQL 8.0+
-- 使用方法: mysql -u用户名 -p 数据库名 < migration_search.sql
-- ================================================================

-- software 表：对 title 和 content 字段建立全文索引
-- 如果已存在旧的 ft_title_desc 索引先删除（忽略错误避免中断）
-- 使用存储过程安全地删除已存在的索引
SET @sql_drop = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE table_schema = DATABASE() AND table_name = 'software' AND index_name = 'ft_title_desc') > 0,
    'ALTER TABLE software DROP INDEX ft_title_desc;',
    'SELECT 1;'
));
PREPARE stmt_drop FROM @sql_drop;
EXECUTE stmt_drop;
DEALLOCATE PREPARE stmt_drop;

-- 添加新的 FULLTEXT 索引 ft_search (title, content)
SET @sql_add_sw = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE table_schema = DATABASE() AND table_name = 'software' AND index_name = 'ft_search') = 0,
    'ALTER TABLE software ADD FULLTEXT INDEX ft_search (title, content);',
    'SELECT 1;'
));
PREPARE stmt_add_sw FROM @sql_add_sw;
EXECUTE stmt_add_sw;
DEALLOCATE PREPARE stmt_add_sw;

-- category 表：对 name 和 description 字段建立全文索引
SET @sql_add_cat = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE table_schema = DATABASE() AND table_name = 'category' AND index_name = 'ft_search') = 0,
    'ALTER TABLE category ADD FULLTEXT INDEX ft_search (name, description);',
    'SELECT 1;'
));
PREPARE stmt_add_cat FROM @sql_add_cat;
EXECUTE stmt_add_cat;
DEALLOCATE PREPARE stmt_add_cat;

-- 验证索引是否创建成功
SELECT 
    TABLE_NAME, 
    INDEX_NAME, 
    INDEX_TYPE,
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS COLUMNS
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE table_schema = DATABASE() 
  AND table_name IN ('software', 'category') 
  AND index_name = 'ft_search'
GROUP BY TABLE_NAME, INDEX_NAME, INDEX_TYPE;
