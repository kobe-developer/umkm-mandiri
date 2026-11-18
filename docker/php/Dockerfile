# syntax=docker/dockerfile:1

# ---------- base: PHP runtime + extensions (Alpine) ----------
FROM php:8.4-fpm-alpine AS base

# installer di-mount sementara, tidak ikut tersimpan di layer image
RUN --mount=type=bind,from=mlocati/php-extension-installer:2,source=/usr/bin/install-php-extensions,target=/usr/local/bin/install-php-extensions \
    install-php-extensions pdo_mysql gd zip bcmath intl pcntl opcache exif \
    && apk add --no-cache tzdata \
    && cp /usr/share/zoneinfo/Asia/Jakarta /etc/localtime \
    && echo "Asia/Jakarta" > /etc/timezone \
    && apk del tzdata

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
WORKDIR /var/www/html

# ---------- dev: source di-bind-mount, tool lengkap ----------
FROM base AS dev
RUN apk add --no-cache mariadb-client unzip git
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
CMD ["php-fpm"]

# ---------- vendor: composer install (composer tidak ikut ke prod) ----------
FROM base AS vendor
ENV COMPOSER_CACHE_DIR=/tmp/composer-cache
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache \
    composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress
COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi

# ---------- assets: build Vite bundle ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN --mount=type=cache,target=/root/.npm npm ci
COPY . .
RUN npm run build

# ---------- prod: hanya runtime + kode + vendor + aset ----------
FROM base AS prod
ENV APP_ENV=production
COPY --from=vendor /var/www/html /var/www/html
COPY --from=assets /app/public/build ./public/build
RUN ln -sfn ../storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache
USER www-data
CMD ["php-fpm"]

# ---------- web: nginx + aset publik ----------
FROM nginx:1.27-alpine AS web
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=prod /var/www/html/public /var/www/html/public
