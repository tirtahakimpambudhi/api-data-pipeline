# ---------- Composer binary ----------
FROM composer:2 AS composerbase

# ---------- PHP-FPM (build + runtime) ----------
FROM php:8.3-fpm-alpine AS phpapp

# 1) System deps
RUN set -eux; \
    apk add --no-cache \
        bash curl git unzip \
        icu-dev oniguruma-dev libzip-dev \
        freetype-dev libjpeg-turbo-dev libpng-dev libwebp-dev \
        sqlite-dev \
        zlib-dev libxml2-dev \
        nodejs npm

# 2) Build toolchain for PHP extensions
RUN set -eux; \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS

# 3) PHP extensions
RUN set -eux; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install -j$(nproc) \
        gd \
        intl \
        mbstring \
        bcmath \
        zip \
        pdo_mysql \
        pdo_sqlite 

# 4) Clean toolchain
RUN set -eux; \
    apk del --no-network .build-deps

# Composer
COPY --from=composerbase /usr/bin/composer /usr/bin/composer

# App code
WORKDIR /var/www
COPY . .

ENV APP_DEBUG=false \
    APP_KEY=${APP_KEY} \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/var/www/database/database.sqlite 

RUN set -eux; \
    composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader; \
    npm ci; \
    npm run build; \
    mkdir -p /var/www/database; \
    touch /var/www/database/database.sqlite; \
    chown -R www-data:www-data storage bootstrap/cache database; \
    find storage -type d -exec chmod 775 {} \;; \
    find storage -type f -exec chmod 664 {} \;; \
    chmod -R 775 bootstrap/cache; \
    # Optional migrate saat build
    php artisan migrate --force; \
    composer require fakerphp/faker; \
    # Optional
    php artisan db:seed --class ProdSeeder  --force;  \
    composer remove fakerphp/faker;


EXPOSE 9000
CMD ["php-fpm"]

# ---------- Nginx ----------
FROM nginx:alpine AS nginxapp
COPY --from=phpapp /var/www/public /var/www/public
COPY nginx/default.conf /etc/nginx/conf.d/default.conf
