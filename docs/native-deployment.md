# 帝国下载系统 V3.0 — 原生部署指南（无 Docker）

> 适用场景：CentOS 7/8、Ubuntu 20.04/22.04、Rocky Linux 8/9  
> 需要的运行环境：Nginx + PHP 8.1+ + MySQL 8.0 + Redis 7.x（可选）

---

## 一、环境准备

### 1.1 安装 PHP 8.3 + 扩展

```bash
# ========== Ubuntu 22.04 ==========
sudo apt update
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt install -y php8.3 php8.3-fpm php8.3-mysql php8.3-mbstring \
    php8.3-xml php8.3-curl php8.3-bcmath php8.3-zip php8.3-gd \
    php8.3-redis composer

# ========== CentOS 7/8 / Rocky Linux ==========
# 先装 EPEL + Remi 仓库
sudo dnf install -y epel-release
sudo dnf install -y https://rpms.remirepo.net/enterprise/remi-release-$(rpm -E %rhel).rpm
sudo dnf module enable -y php:remi-8.3
sudo dnf install -y php php-fpm php-mysqlnd php-mbstring php-xml \
    php-curl php-bcmath php-zip php-gd php-pecl-redis composer
```

### 1.2 安装 MySQL 8.0

```bash
# Ubuntu
sudo apt install -y mysql-server-8.0

# CentOS / Rocky
sudo dnf install -y mysql-server
sudo systemctl start mysqld
sudo systemctl enable mysqld

# 安全初始化（设 root 密码、删匿名用户等）
sudo mysql_secure_installation
```

### 1.3 安装 Nginx

```bash
# Ubuntu
sudo apt install -y nginx

# CentOS / Rocky
sudo dnf install -y nginx
sudo systemctl start nginx
sudo systemctl enable nginx
```

### 1.4 安装 Redis（可选，生产建议）

```bash
# Ubuntu
sudo apt install -y redis-server

# CentOS / Rocky
sudo dnf install -y redis
sudo systemctl start redis
sudo systemctl enable redis
```

---

## 二、创建数据库

```bash
mysql -u root -p <<SQL
CREATE DATABASE IF NOT EXISTS empiredown
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'edown'@'localhost' IDENTIFIED BY 'edown_secret';
GRANT ALL PRIVILEGES ON empiredown.* TO 'edown'@'localhost';
FLUSH PRIVILEGES;
SQL
```

### 导入表结构和种子数据

```bash
mysql -u edown -pedown_secret empiredown < backend/docker/mysql/schema_full.sql
```

> schema_full.sql 包含 38 张表 + 种子数据（管理员 admin/admin123、支付渠道等）

---

## 三、部署后端代码

### 3.1 上传代码

```bash
# 假设部署到 /www/edown/
sudo mkdir -p /www/edown
sudo chown -R $USER:$USER /www/edown

# 上传/解压项目
cd /www/edown
git clone git@github.com:Lansedongli/lanse_download.git .
# 或者 rsync/scp 上传 backend 目录到 /www/edown/
```

### 3.2 安装 PHP 依赖

```bash
cd /www/edown/backend
composer install --no-dev --optimize-autoloader
```

### 3.3 配置环境变量

```bash
cd /www/edown/backend
cp .env.example .env

# 编辑 .env，关键配置如下：
vim .env
```

```ini
# .env — 生产环境配置
APP_NAME = 帝国下载系统
APP_ENV = production
APP_DEBUG = false

# 数据库（MySQL 本机）
DB_HOST = 127.0.0.1
DB_PORT = 3306
DB_DATABASE = empiredown
DB_USERNAME = edown
DB_PASSWORD = edown_secret
DB_CHARSET = utf8mb4
DB_PREFIX =

# Redis（本机）
REDIS_HOST = 127.0.0.1
REDIS_PORT = 6379
REDIS_PASSWORD =

# JWT（务必换成随机字符串！）
# 生成命令: openssl rand -base64 64
JWT_SECRET = 换成随机64位base64字符串
JWT_TTL = 7200

# 文件存储
UPLOAD_PATH = /www/edown/backend/public/uploads
UPLOAD_MAX_SIZE = 104857600

# 缓存（生产用 redis，测试可用 file）
CACHE_DRIVER = redis
SESSION_DRIVER = redis
```

### 3.4 创建目录 + 设权限

```bash
cd /www/edown/backend
mkdir -p runtime/log runtime/cache public/uploads public/static
chmod -R 775 runtime
chmod -R 775 public/uploads public/static
chown -R www-data:www-data runtime public/uploads   # Ubuntu
# 或 chown -R nginx:nginx runtime public/uploads    # CentOS/Rocky
```

---

## 四、配置 Nginx

### 4.1 创建站点配置

```bash
sudo vim /etc/nginx/sites-available/edown.conf   # Ubuntu
# 或 /etc/nginx/conf.d/edown.conf                # CentOS/Rocky
```

```nginx
server {
    listen 80;
    server_name your-domain.com;    # 换成你的域名或 IP
    root /www/edown/backend/public;
    index index.php index.html;

    charset utf-8;

    # ===== 安全头部 =====
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # ===== 禁止访问敏感文件 =====
    location ~ ^/(runtime|vendor|\.env|composer\.(json|lock)|think)$ {
        deny all;
        return 404;
    }

    # ===== 禁止访问隐藏文件 =====
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    # ===== 静态文件 =====
    location /static/ {
        alias /www/edown/backend/public/static/;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    location /uploads/ {
        alias /www/edown/backend/public/uploads/;
        expires 7d;
    }

    # ===== ThinkPHP 伪静态 =====
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # ===== PHP 处理 =====
    location ~ \.php$ {
        # Ubuntu: php8.3-fpm.sock | CentOS: php-fpm
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        include fastcgi_params;
    }

    # ===== 上传限制 =====
    client_max_body_size 100m;
    client_body_timeout 60s;

    # ===== 日志 =====
    access_log /var/log/nginx/edown_access.log;
    error_log /var/log/nginx/edown_error.log warn;
}
```

### 4.2 启用站点

```bash
# Ubuntu
sudo ln -s /etc/nginx/sites-available/edown.conf /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

# CentOS/Rocky 直接用 conf.d 中的配置，无需额外操作

# 测试配置
sudo nginx -t

# 重载
sudo systemctl reload nginx
```

---

## 五、配置 PHP-FPM

### 5.1 创建进程池配置

```bash
# Ubuntu
sudo vim /etc/php/8.3/fpm/pool.d/edown.conf

# CentOS/Rocky
sudo vim /etc/php-fpm.d/edown.conf
```

```ini
[edown]
user = www-data                  ; Ubuntu 用 www-data，CentOS 用 nginx
group = www-data

listen = /var/run/php/php8.3-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 10

; 安全：限制 PHP 脚本只能访问项目目录
php_admin_value[open_basedir] = /www/edown/backend/:/tmp/
php_admin_value[upload_max_filesize] = 100M
php_admin_value[post_max_size] = 100M
php_admin_value[max_execution_time] = 300
php_admin_value[memory_limit] = 256M
```

### 5.2 重启 PHP-FPM

```bash
# Ubuntu
sudo systemctl restart php8.3-fpm

# CentOS/Rocky
sudo systemctl restart php-fpm
```

---

## 六、启动 Queue Worker（可选）

```bash
# 用 systemd 管理队列消费者
sudo vim /etc/systemd/system/edown-queue.service
```

```ini
[Unit]
Description=帝国下载系统 Queue Worker
After=network.target mysql.service redis.service

[Service]
User=www-data
Group=www-data
WorkingDirectory=/www/edown/backend
ExecStart=/usr/bin/php think queue:listen --queue default --sleep 3
Restart=on-failure
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable edown-queue
sudo systemctl start edown-queue
```

---

## 七、验证部署

```bash
# 1. 检查 Nginx 状态
curl -I http://localhost/
# 应返回 HTTP/1.1 200 OK（或者 302 跳转）

# 2. 测试后台 API
curl -X POST http://localhost/admin/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
# 应返回 {"code":200,"message":"登录成功","data":{"tokens":{...}}}

# 3. 检查日志
tail -f /www/edown/backend/runtime/log/$(date +%Y%m)/$(date +%d).log
```

---

## 八、一键部署脚本

```bash
#!/bin/bash
# 保存为 deploy.sh，在服务器上执行

set -e

PROJECT_DIR="/www/edown"
PHP_FPM_USER="www-data"   # Ubuntu: www-data, CentOS: nginx

echo "=== 帝国下载系统 V3.0 原生部署 ==="

# 1. 拉代码
cd $PROJECT_DIR/backend

# 2. 安装依赖
composer install --no-dev --optimize-autoloader

# 3. 配置 .env（交互式）
if [ ! -f .env ]; then
    cp .env.example .env
    JWT_SECRET=$(openssl rand -base64 64)
    sed -i "s/JWT_SECRET=.*/JWT_SECRET=${JWT_SECRET}/" .env
    echo "请编辑 .env 文件填入数据库密码等信息: vim .env"
fi

# 4. 创建目录
mkdir -p runtime/log runtime/cache public/uploads public/static
chmod -R 775 runtime public/uploads
chown -R $PHP_FPM_USER:$PHP_FPM_USER runtime public/uploads

# 5. 导入数据库（如果还没导入）
# mysql -u edown -p empiredown < docker/mysql/schema_full.sql

# 6. 重启服务
sudo systemctl reload nginx
# sudo systemctl restart php8.3-fpm  # Ubuntu
# sudo systemctl restart php-fpm     # CentOS

echo "=== 部署完成 ==="
echo "后台地址: http://your-domain.com/admin/api/auth/login"
echo "管理员账号: admin / admin123"
```

---

## 九、目录权限速查

| 目录/文件 | 权限 | 所有者 | 说明 |
|-----------|:----:|--------|------|
| `backend/` | 755 | 部署用户 | 项目根目录 |
| `vendor/` | 755 | 部署用户 | Composer 依赖 |
| `runtime/` | **775** | **www-data** | 日志+缓存，PHP 必须可写 |
| `public/uploads/` | **775** | **www-data** | 上传目录 |
| `public/static/` | **775** | **www-data** | 静态文件目录 |
| `.env` | 640 | 部署用户 | 敏感配置，禁止 Web 读取 |

---

## 十、常见问题

| 问题 | 解决 |
|------|------|
| **502 Bad Gateway** | PHP-FPM 没启动或 socket 路径不对：`systemctl status php8.3-fpm` |
| **500 Internal Error** | 查看 `runtime/log/` 日志，通常是 DB 连不上或目录没写权限 |
| **file_put_contents 失败** | `chmod -R 775 runtime && chown -R www-data runtime` |
| **PDO 驱动找不到** | `php -m \| grep pdo_mysql`，没装就装 php8.3-mysql |
| **Redis 连不上** | `CACHE_DRIVER=file` 和 `SESSION_DRIVER=file` 先避开 Redis |
| **图片上传失败** | `public/uploads/` 目录不存在或不可写 |
