#!/bin/sh
set -e

# 修复挂载目录权限（Docker volume mount 会覆盖镜像内的权限）
# GitHub Actions runner 的 UID 通常是 1001，PHP-FPM 以 www-data(33) 运行
# 确保 runtime 目录可写
mkdir -p /var/www/html/runtime
chown -R www-data:www-data /var/www/html/runtime 2>/dev/null || true
chmod -R 775 /var/www/html/runtime 2>/dev/null || true

# 确保 public/static 和 public/uploads 可写
mkdir -p /var/www/html/public/static /var/www/html/public/uploads
chown -R www-data:www-data /var/www/html/public/static /var/www/html/public/uploads 2>/dev/null || true

# 启动 Supervisor
exec /usr/bin/supervisord -c /etc/supervisord.conf
