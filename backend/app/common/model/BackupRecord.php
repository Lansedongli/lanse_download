<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 备份记录模型
 * 对应表: ed_backup_record (id, filename, size, type, created_at)
 */
class BackupRecord extends BaseModel
{
    /** @var string 表名 */
    protected $name = 'backup_record';

    /** @var bool 关闭自动更新时间戳（此表无 updated_at） */
    protected $updateTime = false;
}
