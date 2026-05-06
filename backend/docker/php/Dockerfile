# ============================================
# PHP 8.1 FPM Dockerfile
# ThinkPHP 8.1 Runtime
# ============================================
FROM php:8.1-fpm-alpine

# Build arguments
ARG PHP_VERSION=8.1

LABEL maintainer="edown@example.com"
LABEL description="ThinkPHP 8.1 + PHP 8.1 Runtime"

# Install system dependencies
RUN set -ex && \
    apk update && \
    apk add --no-cache \
        # Build tools
        autoconf g++ make \
        # Runtime libs
        libpng-dev libjpeg-turbo-dev freetype-dev \
        libzip-dev oniguruma-dev icu-dev \
        # Other utils
        git curl wget unzip zip \
        supervisor bash tzdata \
    && docker-php-ext-configure gd \
        --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        bcmath \
        gd \
        zip \
        mbstring \
        intl \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del autoconf g++ make \
    && rm -rf /var/cache/apk/* /tmp/*

# Set timezone
RUN ln -sf /usr/share/zoneinfo/Asia/Shanghai /etc/localtime && \
    echo "Asia/Shanghai" > /etc/timezone

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP Configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-edown.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-edown.conf

# Working directory
WORKDIR /var/www/html

# Copy application
COPY . /var/www/html

# Install dependencies
RUN if [ -f composer.json ]; then \
        composer install --no-dev --optimize-autoloader --no-interaction; \
    fi

# Permissions
RUN chown -R www-data:www-data /var/www/html/runtime && \
    chown -R www-data:www-data /var/www/html/public/uploads

EXPOSE 9000

CMD ["php-fpm", "-F"]
