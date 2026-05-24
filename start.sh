#!/bin/sh

#
mkdir -p /var/www/html/storage/logs
touch /var/www/html/storage/logs/laravel.log
chown -r www-data:www-data /var/www/html/*

# Cache configuration for production
if [ "$APP_ENV" = "production" ]; then
    # Clear package discovery cache
    rm -f bootstrap/cache/packages.php
    rm -f bootstrap/cache/services.php

    # Discover packages (only installed ones, no dev)
    php artisan package:discover --ansi

    # Cache config
    php artisan config:cache
fi

# Start PHP-FPM
php-fpm -D

# Start Caddy
mkdir -p /var/log/caddy
caddy run --config /etc/caddy/Caddyfile --adapter caddyfile
