<?php
declare(strict_types=1);

use think\migration\Seeder;

/**
 * 数据库初始化数据填充
 * 会员组、角色、超级管理员、模板变量
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. 会员组
        $groups = [
            ['name' => '普通会员', 'code' => 'normal', 'level' => 1, 'is_default' => 1, 'max_favorites' => 100, 'max_download_day' => 10, 'discount' => 1.00, 'description' => '注册即享'],
            ['name' => 'VIP会员',  'code' => 'vip',    'level' => 2, 'is_default' => 0, 'max_favorites' => 500, 'max_download_day' => 50, 'discount' => 0.80, 'description' => 'VIP特权'],
            ['name' => 'SVIP会员', 'code' => 'svip',   'level' => 3, 'is_default' => 0, 'max_favorites' => 9999,'max_download_day' => 0,  'discount' => 0.50, 'description' => 'SVIP无限下载'],
        ];
        foreach ($groups as $g) {
            $this->table('user_group')->insert($g);
        }

        // 2. 角色 (RBAC)
        $this->table('role')->insert([
            'name'        => '超级管理员',
            'code'        => 'super_admin',
            'permissions' => json_encode(['*']),
            'description' => '拥有所有权限',
        ]);
        $this->table('role')->insert([
            'name'        => '内容编辑',
            'code'        => 'editor',
            'permissions' => json_encode(['software:list','software:create','software:update','category:list']),
            'description' => '内容编辑权限',
        ]);

        // 3. 超级管理员 (密码: admin123)
        $this->table('admin_user')->insert([
            'username' => 'admin',
            'password' => password_hash('admin123', PASSWORD_BCRYPT),
            'nickname' => '超级管理员',
            'role_id'  => 1,
        ]);

        // 4. 模板变量
        $vars = [
            ['name' => 'site_name',        'value' => '帝国下载站',         'description' => '站点名称'],
            ['name' => 'site_keywords',    'value' => '软件下载,免费软件',  'description' => '站点关键词'],
            ['name' => 'site_description', 'value' => '专业软件资源下载平台', 'description' => '站点描述'],
            ['name' => 'copyright',        'value' => '© 2026 帝国下载站', 'description' => '版权信息'],
        ];
        foreach ($vars as $v) {
            $this->table('template_var')->insert($v);
        }

        echo "✓ Database seeded successfully!\n";
    }
}
