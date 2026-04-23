#!/bin/bash
set -e

cd /var/www/html

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Replace PORT in nginx config dynamically
PORT=${PORT:-8080}
sed -i "s/listen 8080 default_server/listen $PORT default_server/g" /etc/nginx/nginx.conf
sed -i "s/listen \[::\]:8080 default_server/listen [::]:$PORT default_server/g" /etc/nginx/nginx.conf

# Run migrations
php artisan migrate --force

# Cache config for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start php-fpm in background
php-fpm -D
sleep 2

# Test nginx config first
echo "Testing nginx config..."
nginx -t

echo "Starting nginx on port $PORT..."
# Start nginx in foreground
exec nginx -g "daemon off;"