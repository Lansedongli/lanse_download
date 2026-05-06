FROM php:8.3-fpm

# 基础依赖
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    unzip \
    git \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# PHP 扩展
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        zip \
        pdo \
        pdo_mysql \
        mysqli \
        mbstring \
        xml \
        curl \
        bcmath \
        sockets \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# 工作目录
WORKDIR /var/www/html

# 权限
RUN chown -R www-data:www-data /var/www/html

# PHP 配置
COPY docker/php/php.ini /usr/local/etc/php/conf.d/edown.ini

# Supervisor 配置
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 9000

CMD ["supervisord", "-c", "/etc/supervisord.conf"]
