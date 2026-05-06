# 帝国下载系统 V3.0

> ThinkPHP 8.1 + PHP 8.1 + MySQL 8.0 + Redis + Vue3

## 技术栈

| 层级 | 技术 |
|------|------|
| 后端框架 | ThinkPHP 8.1.x |
| PHP 版本 | 8.1+ |
| 数据库 | MySQL 8.0+ |
| 缓存 | Redis 7.x |
| 容器化 | Docker + Docker Compose |

## 项目结构

```
├── app/
│   ├── admin/          # 后台 API
│   │   └── controller/ # 后台控制器
│   ├── api/            # 前台 API
│   │   └── controller/ # 前台控制器
│   ├── common/         # 公共模块
│   │   ├── enum/       # PHP 8.1 枚举
│   │   ├── model/      # 模型
│   │   ├── service/    # 服务层
│   │   ├── middleware/  # 中间件
│   │   ├── exception/  # 异常处理
│   │   └── helper/     # 助手函数
│   └── command/        # CLI 命令
├── config/             # 配置文件
├── database/
│   ├── migrations/     # 数据库迁移
│   └── seeds/          # 数据填充
├── docker/             # Docker 配置
├── route/              # 路由定义
│   ├── api.php         # 前台 API 路由
│   └── admin.php       # 后台 API 路由
├── public/             # Web 根目录
├── docker-compose.yml
└── install.sh
```

## 快速开始

```bash
# 1. 克隆项目
cd /path/to/edown

# 2. 安装依赖
composer install

# 3. 配置环境
cp .env.example .env

# 4. 启动 Docker
docker-compose up -d

# 5. 导入数据库
docker-compose exec mysql mysql -uroot -proot_secret empiredown < ../database_schema.sql
```

## API 接口

| 接口 | 方法 | URL |
|------|------|-----|
| 用户注册 | POST | /api/v1/auth/register |
| 用户登录 | POST | /api/v1/auth/login |
| 刷新Token | POST | /api/v1/auth/refresh |
| 用户信息 | GET | /api/v1/user/me |
| 管理员登录 | POST | /admin/api/auth/login |

## 默认账号

- 后台管理员: `admin` / `admin123`
