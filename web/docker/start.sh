#!/bin/bash
set -e

cd /var/www/html

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Run migrations
php artisan migrate --force

# Cache config for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start php-fpm in background
php-fpm -D

# Start nginx in foreground
nginx -g "daemon off;"