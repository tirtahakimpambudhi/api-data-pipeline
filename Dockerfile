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
        nodejs npm \
        $PHPIZE_DEPS; \
    # 2) PHP extensions
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install -j$(nproc) \
        gd \
        intl \
        mbstring \
        bcmath \
        zip \
        pdo_mysql \
        pdo_sqlite; \
    # 3) Clean toolchain dan cache
    apk del --no-network $PHPIZE_DEPS; \
    rm -rf /var/cache/apk/* /tmp/* /var/tmp/*

# Composer
COPY --from=composerbase /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# 4) Copy dependency files and entrypoint file
COPY composer.json composer.lock ./
COPY package.json package-lock.json ./
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# 5) Install dependencies
RUN set -eux; \
    composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --no-scripts; \
    npm ci; \
    # Clean composer cache
    composer clear-cache; \
    rm -rf /root/.composer/cache

# 6) Copy app code
COPY . .

# 7) Build assets and setup
RUN set -eux; \
    # Run composer scripts
    composer dump-autoload --optimize; \
    # Build frontend assets
    npm run build; \
    # Remove node_modules after build
    rm -rf node_modules; \
    apk del --no-network nodejs npm; \
    # Setup database
    mkdir -p /var/www/database; \
    touch /var/www/database/database.sqlite; \
    # Permissions
    chown -R www-data:www-data storage bootstrap/cache database; \
    find storage -type d -exec chmod 775 {} \;; \
    find storage -type f -exec chmod 664 {} \;; \
    chmod -R 775 bootstrap/cache; \
    # Clean unnecessary files
    rm -rf tests .git .github .env.example README.md phpunit.xml /root/.npm /tmp/* /var/tmp/*

ENV APP_DEBUG=false \
    APP_KEY=${APP_KEY} \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/var/www/database/database.sqlite

EXPOSE 9000
CMD ["/usr/local/bin/entrypoint.sh"]

# ---------- Nginx ----------
FROM nginx:alpine AS nginxapp
COPY --from=phpapp /var/www/public /var/www/public
COPY nginx/default.conf /etc/nginx/conf.d/default.conf

# Clean nginx
RUN rm -rf /var/cache/apk/*

EXPOSE 80
