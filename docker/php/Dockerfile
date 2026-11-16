# syntax=docker/dockerfile:1

# ---------- base: PHP runtime + extensions ----------
FROM php:8.4-fpm-bookworm AS base

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql gd zip bcmath intl pcntl opcache exif \
    && apt-get update && apt-get install -y --no-install-recommends default-mysql-client unzip \
    && rm -rf /var/lib/apt/lists/* \
    && ln -sf /usr/share/zoneinfo/Asia/Jakarta /etc/localtime \
    && echo "Asia/Jakarta" > /etc/timezone

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html

# ---------- dev: source is bind-mounted ----------
FROM base AS dev
CMD ["php-fpm"]

# ---------- assets: build Vite bundle once ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci
COPY . .
RUN npm run build

# ---------- prod: code + vendor + built assets ----------
FROM base AS prod
ENV APP_ENV=production
COPY . .
COPY --from=assets /app/public/build ./public/build
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress \
    && ln -sfn ../storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache
USER www-data
CMD ["php-fpm"]

# ---------- web: nginx + aset publik (production) ----------
FROM nginx:1.27-alpine AS web
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=prod /var/www/html/public /var/www/html/public
