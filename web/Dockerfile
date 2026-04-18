# ── Stage 1: Node (build Vite assets) ──────────────────────────────────────
FROM node:22-alpine AS node-builder

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .
RUN npm run build

# ── Stage 2: PHP (run Laravel) ─────────────────────────────────────────────
FROM php:8.2-fpm-alpine AS php-builder

# Install system dependencies
RUN apk add --no-cache \
    bash \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    postgresql-dev \
    oniguruma-dev

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    zip \
    gd \
    bcmath

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files and install dependencies
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy the rest of the app
COPY . .

# Copy built Vite assets from node stage
COPY --from=node-builder /app/public/build ./public/build

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# ── Stage 3: Final image with nginx + php-fpm ──────────────────────────────
FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    nginx \
    bash \
    postgresql-dev \
    libpng-dev \
    libzip-dev \
    oniguruma-dev

RUN docker-php-ext-install pdo pdo_pgsql pgsql mbstring zip gd bcmath

WORKDIR /var/www/html

COPY --from=php-builder /var/www/html /var/www/html
COPY --from=php-builder /usr/bin/composer /usr/bin/composer

# Nginx config
RUN mkdir -p /run/nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Startup script
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]