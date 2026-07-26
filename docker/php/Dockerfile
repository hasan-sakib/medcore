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

# Frontend assets builder stage
FROM node:20-alpine AS frontend-builder
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# Production stage — bake in app
FROM base AS production

# Caching composer installs
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY --chown=www-data:www-data . /var/www/html
COPY --from=frontend-builder --chown=www-data:www-data /app/public/build /var/www/html/public/build

RUN mkdir -p storage/framework/views storage/framework/cache/data storage/framework/sessions storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && composer dump-autoload --optimize --no-dev --classmap-authoritative \
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
