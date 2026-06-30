FROM php:8.3-fpm-alpine AS base

LABEL maintainer="MedCore Team"

# System deps
RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    autoconf \
    g++ \
    make \
    linux-headers

# PHP extensions
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        zip \
        gd \
        bcmath \
        intl \
        xml \
        opcache \
        pcntl \
        posix \
        sockets

# Redis extension via PECL
RUN pecl install redis \
 && docker-php-ext-enable redis

# Cleanup build deps
RUN apk del autoconf g++ make linux-headers \
 && rm -rf /tmp/pear /var/cache/apk/*

# Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Production stage — bake in app
FROM base AS production

COPY --chown=www-data:www-data . /var/www/html

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
 && php artisan config:cache \
 && php artisan route:cache \
 && php artisan view:cache

COPY docker/php/php.ini /usr/local/etc/php/conf.d/medcore.ini
COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]

# Development stage — no baked code; bind-mount instead
FROM base AS development

COPY docker/php/php.ini /usr/local/etc/php/conf.d/medcore.ini
COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]
