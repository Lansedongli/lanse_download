#!/bin/bash
set -e

echo "======================================"
echo " 帝国下载系统 V3.0 安装脚本"
echo " ThinkPHP 8.1 + PHP 8.1"
echo "======================================"

# 检查环境
command -v composer >/dev/null 2>&1 || { echo "请先安装 Composer"; exit 1; }
command -v docker >/dev/null 2>&1 || { echo "请先安装 Docker"; exit 1; }
command -v docker-compose >/dev/null 2>&1 || { echo "请先安装 Docker Compose"; exit 1; }

# 安装依赖
echo ">>> 安装 PHP 依赖..."
composer install --no-dev --optimize-autoloader

# 复制环境变量
if [ ! -f .env ]; then
    cp .env.example .env 2>/dev/null || echo "请手动配置 .env 文件"
fi

# 生成 JWT Secret
if grep -q "JWT_SECRET=" .env; then
    JWT_SECRET=\$(openssl rand -base64 64)
    sed -i "s/JWT_SECRET=.*/JWT_SECRET=\${JWT_SECRET}/" .env
fi

# 创建必要目录
mkdir -p runtime/log runtime/cache public/uploads
chmod -R 777 runtime

echo ""
echo ">>> 启动 Docker 容器..."
docker-compose up -d

# 等待 MySQL 就绪
echo ">>> 等待 MySQL 启动..."
sleep 10

# 导入数据库
echo ">>> 导入数据库..."
docker-compose exec -T mysql mysql -uroot -proot_secret empiredown < database/schema.sql 2>/dev/null || echo "数据库已存在，跳过导入"

echo ""
echo "======================================"
echo " ✓ 安装完成!"
echo ""
echo " 前台 API: http://localhost:8080/api/v1"
echo " 后台 API: http://localhost:8080/admin/api"
echo ""
echo " 后台管理员: admin / admin123"
echo "======================================"
