#!/bin/bash
set -e

cd /var/www/html

# Create .env file from Railway environment variables
cat > .env << EOF
APP_NAME="Where Is My Flight"
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_URL=${APP_URL}

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite

SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_DRIVER=sync

FLIGHT_API_PROVIDER=aviationstack
AVIATIONSTACK_API_KEY=${AVIATIONSTACK_API_KEY}
OPENWEATHER_API_KEY=${OPENWEATHER_API_KEY}
SERPAPI_KEY=${SERPAPI_KEY}
EOF

# Create SQLite database
touch /var/www/html/database/database.sqlite

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Replace PORT in nginx config
PORT=${PORT:-8080}
sed -i "s/__PORT__/$PORT/g" /etc/nginx/nginx.conf

# Run migrations
php artisan migrate --force

# Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start php-fpm
php-fpm -D
sleep 2

# Test and start nginx
nginx -t
echo "Starting nginx on port $PORT..."
exec nginx -g "daemon off;"