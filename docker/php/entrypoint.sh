#!/bin/bash
set -e

# Ensure log directory exists
mkdir -p /var/log/php
chown -R www-data:www-data /var/log/php 2>/dev/null || true

# Ensure storage/bootstrap/cache directories are writable
if [ -d /var/www/html ]; then
    mkdir -p \
        /var/www/html/storage/app/public \
        /var/www/html/storage/framework/cache/data \
        /var/www/html/storage/framework/sessions \
        /var/www/html/storage/framework/testing \
        /var/www/html/storage/framework/views \
        /var/www/html/storage/logs \
        /var/www/html/bootstrap/cache

    chown -R www-data:www-data \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache 2>/dev/null || true

    chmod -R 775 \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache 2>/dev/null || true
fi

exec "$@"
