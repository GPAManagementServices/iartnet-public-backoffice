# STAGE 1: Base Runtime (Debian Bookworm)
FROM php:8.4-fpm-bookworm AS base

# Iniezione deterministica dei parametri di tuning PHP
COPY infra/docker/app/php.custom.ini /usr/local/etc/php/conf.d/zz-custom.ini

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libwebp-dev \
    libzip-dev zip unzip git curl libmagickwand-dev \
    libicu-dev --no-install-recommends \
    && pecl install imagick redis \
    && docker-php-ext-enable imagick redis \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install gd pdo_mysql zip opcache bcmath intl exif \
    && rm -rf /var/lib/apt/lists/*

# STAGE 2: Build (Composer)
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs

# STAGE 3: Final Production Image
FROM base AS final
WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY infra/docker/app/start.sh /usr/local/bin/start.sh

RUN php -r "file_exists('vendor/autoload.php') || exit(1);" \
    && mkdir -p \
        storage/app/public \
        storage/app/private \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
        /var/www/.config/psysh \
    && chmod +x /usr/local/bin/start.sh \
    && chown -R www-data:www-data storage bootstrap/cache /var/www/.config \
    && chmod -R 775 storage bootstrap/cache /var/www/.config

USER www-data
CMD ["/usr/local/bin/start.sh"]
