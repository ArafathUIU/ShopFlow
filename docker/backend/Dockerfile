FROM php:8.3-fpm-alpine AS builder

RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libxpm-dev \
    libzip-dev \
    libxml2-dev \
    icu-dev \
    oniguruma-dev \
    libpq-dev \
    nodejs \
    npm \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-xpm \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_pgsql \
        zip \
        xml \
        dom \
        simplexml

FROM composer:2 AS vendor

WORKDIR /app
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libxpm-dev \
    libzip-dev \
    libxml2-dev \
    icu-dev \
    oniguruma-dev \
    libpq-dev \
    nginx \
    supervisor \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-xpm \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_pgsql \
        zip \
        xml \
        dom \
        simplexml \
    && mkdir -p /var/log/php-fpm /var/log/nginx /var/lib/php/sessions \
    && chown -R www-data:www-data /var/log/php-fpm /var/log/nginx /var/lib/php/sessions

COPY --from=vendor /app/vendor /var/www/html/vendor
COPY backend /var/www/html

WORKDIR /var/www/html

RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist \
    && composer dump-autoload --optimize \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker/backend/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/backend/supervisord.conf /etc/supervisord.conf
COPY docker/backend/php.ini /usr/local/etc/php/conf.d/custom.ini

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
