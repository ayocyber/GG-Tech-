# GG-TECH storefront — production image for Render (free web service).

# 1. PHP dependencies
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs

# 2. Front-end assets (Vite + Tailwind)
FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci --ignore-scripts
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
COPY app ./app
# Tailwind also scans the Laravel and Livewire pagination views.
COPY --from=vendor /app/vendor/laravel/framework/src/Illuminate/Pagination/resources ./vendor/laravel/framework/src/Illuminate/Pagination/resources
COPY --from=vendor /app/vendor/livewire/livewire/src/Features/SupportPagination/views ./vendor/livewire/livewire/src/Features/SupportPagination/views
RUN npm run build

# 3. Runtime: PHP 8.4 + Apache
FROM php:8.4-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpq-dev libzip-dev libicu-dev libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev unzip \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pdo_mysql intl zip gd exif bcmath opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && php artisan package:discover --ansi \
    && php artisan filament:assets \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x docker/start.sh

ENV PORT=10000
EXPOSE 10000

CMD ["docker/start.sh"]
