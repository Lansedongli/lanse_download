<?php
declare(strict_types=1);

use think\migration\Migrator;

/**
 * 创建用户相关基础表
 * user, user_group, user_points_log, user_favorite
 */
class CreateUsersTable extends Migrator
{
    public function up(): void
    {
        // 会员组表
        $this->table('user_group', ['engine' => 'InnoDB', 'comment' => '会员组表'])
             ->addColumn('name', 'string', ['limit' => 100, 'comment' => '会员组名称'])
             ->addColumn('code', 'string', ['limit' => 50, 'comment' => '会员组标识'])
             ->addColumn('level', 'integer', ['default' => 0, 'comment' => '级别'])
             ->addColumn('is_default', 'boolean', ['default' => 0, 'comment' => '是否默认组'])
             ->addColumn('price_month', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0, 'comment' => '包月价'])
             ->addColumn('price_quarter', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0, 'comment' => '包季价'])
             ->addColumn('price_year', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0, 'comment' => '包年价'])
             ->addColumn('max_favorites', 'integer', ['default' => 100, 'comment' => '最大收藏'])
             ->addColumn('max_download_day', 'integer', ['default' => 10, 'comment' => '每日下载上限'])
             ->addColumn('discount', 'decimal', ['precision' => 3, 'scale' => 2, 'default' => 1.00, 'comment' => '折扣'])
             ->addColumn('status', 'boolean', ['default' => 1, 'comment' => '状态'])
             ->addTimestamps('created_at', 'updated_at')
             ->addIndex(['code'], ['unique' => true, 'name' => 'uk_code'])
             ->addIndex(['level'], ['name' => 'idx_level'])
             ->create();

        // 会员用户表
        $this->table('user', ['engine' => 'InnoDB', 'comment' => '会员用户表'])
             ->addColumn('username', 'string', ['limit' => 50, 'comment' => '用户名'])
             ->addColumn('password', 'string', ['limit' => 255, 'comment' => '密码'])
             ->addColumn('email', 'string', ['limit' => 100, 'default' => '', 'comment' => '邮箱'])
             ->addColumn('mobile', 'string', ['limit' => 20, 'default' => '', 'comment' => '手机号'])
             ->addColumn('nickname', 'string', ['limit' => 100, 'default' => '', 'comment' => '昵称'])
             ->addColumn('avatar', 'string', ['limit' => 500, 'default' => '', 'comment' => '头像'])
             ->addColumn('group_id', 'integer', ['default' => 0, 'comment' => '会员组ID'])
             ->addColumn('points', 'integer', ['default' => 0, 'comment' => '点数'])
             ->addColumn('expire_time', 'datetime', ['null' => true, 'comment' => '有效期'])
             ->addColumn('total_recharge', 'decimal', ['precision' => 12, 'scale' => 2, 'default' => 0, 'comment' => '累计充值'])
             ->addColumn('total_download', 'integer', ['default' => 0, 'comment' => '累计下载'])
             ->addColumn('today_download', 'integer', ['default' => 0, 'comment' => '今日下载'])
             ->addColumn('register_ip', 'string', ['limit' => 50, 'default' => '', 'comment' => '注册IP'])
             ->addColumn('login_ip', 'string', ['limit' => 50, 'default' => '', 'comment' => '登录IP'])
             ->addColumn('login_time', 'datetime', ['null' => true, 'comment' => '登录时间'])
             ->addColumn('openid_wechat', 'string', ['limit' => 100, 'default' => '', 'comment' => '微信OpenID'])
             ->addColumn('openid_qq', 'string', ['limit' => 100, 'default' => '', 'comment' => 'QQ OpenID'])
             ->addColumn('status', 'integer', ['default' => 1, 'comment' => '状态'])
             ->addTimestamps('created_at', 'updated_at')
             ->addSoftDelete()
             ->addIndex(['username'], ['unique' => true, 'name' => 'uk_username'])
             ->addIndex(['email'], ['unique' => true, 'name' => 'uk_email'])
             ->addIndex(['group_id'], ['name' => 'idx_group_id'])
             ->addIndex(['status'], ['name' => 'idx_status'])
             ->create();
    }

    public function down(): void
    {
        $this->table('user')->drop();
        $this->table('user_group')->drop();
    }
}
