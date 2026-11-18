# syntax=docker/dockerfile:1

# ---------- base: PHP runtime + extensions (Alpine) ----------
FROM php:8.5-fpm-alpine AS base

# installer & resolv.conf di-mount sementara, tidak ikut tersimpan di layer image.
# gd dikompilasi manual (freetype/jpeg/png/webp saja): installer selalu ikut AVIF + XPM
# (aom, dav1d, libX11) bila Alpine menyediakannya. Data ICU cukup bahasa Inggris karena
# intl hanya dipakai untuk IDN/normalisasi; terjemahan memakai file lang Laravel.
RUN --mount=type=bind,source=docker/resolv.conf,target=/etc/resolv.conf \
    --mount=type=bind,from=mlocati/php-extension-installer:2,source=/usr/bin/install-php-extensions,target=/usr/local/bin/install-php-extensions \
    apk add --no-cache --virtual .gd-build $PHPIZE_DEPS pax-utils \
        freetype-dev libjpeg-turbo-dev libpng-dev libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd \
    && gdRunDeps="$(scanelf --needed --nobanner --format '%n#p' /usr/local/lib/php/extensions/*/gd.so \
        | tr ',' '\n' | sort -u | awk '{ print "so:" $1 }')" \
    && apk add --no-cache --virtual .gd-run $gdRunDeps \
    && apk del --no-network .gd-build \
    && IPE_ICU_EN_ONLY=1 install-php-extensions pdo_mysql zip bcmath intl pcntl opcache exif \
    && apk add --no-cache tzdata \
    && cp /usr/share/zoneinfo/Asia/Jakarta /etc/localtime \
    && echo "Asia/Jakarta" > /etc/timezone \
    && apk del tzdata

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
WORKDIR /var/www/html

# ---------- dev: source di-bind-mount, tool lengkap ----------
FROM base AS dev
RUN --mount=type=bind,source=docker/resolv.conf,target=/etc/resolv.conf \
    apk add --no-cache mariadb-client unzip git
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
CMD ["php-fpm"]

# ---------- vendor: composer install (composer tidak ikut ke prod) ----------
FROM base AS vendor
ENV COMPOSER_CACHE_DIR=/tmp/composer-cache
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN --mount=type=bind,source=docker/resolv.conf,target=/etc/resolv.conf \
    --mount=type=cache,target=/tmp/composer-cache \
    composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress
COPY . .
# chown di stage ini (bukan di prod) supaya storage tidak tersalin dua kali di layer prod
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi \
    && ln -sfn ../storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache

# ---------- assets: build Vite bundle ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN --mount=type=bind,source=docker/resolv.conf,target=/etc/resolv.conf \
    --mount=type=cache,target=/root/.npm npm ci
COPY . .
RUN npm run build

# ---------- prod: hanya runtime + kode + vendor + aset ----------
FROM base AS prod
ENV APP_ENV=production
COPY --from=vendor /var/www/html /var/www/html
COPY --from=assets /app/public/build ./public/build
USER www-data
CMD ["php-fpm"]

# ---------- web: nginx + aset publik ----------
FROM nginx:1.27-alpine AS web
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=prod /var/www/html/public /var/www/html/public
