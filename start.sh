#!/bin/sh

#
mkdir -p /var/www/html/storage/logs
touch /var/www/html/storage/logs/laravel.log
chown -R www-data:www-data /var/www/html/*

if [ "$APP_ENV" = "production" ]; then
    rm -f bootstrap/cache/packages.php
    rm -f bootstrap/cache/services.php

    php artisan package:discover --ansi
    php artisan migrate --force --ansi
    php artisan config:cache
fi

# Start PHP-FPM
php-fpm -D

# Start Caddy
mkdir -p /var/log/caddy
caddy run --config /etc/caddy/Caddyfile --adapter caddyfile
