FROM php:8.3-fpm-alpine

# 必要拡張
RUN apk add --no-cache git zip unzip libpng-dev libjpeg-turbo-dev libwebp-dev icu-dev oniguruma-dev shadow bash \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_mysql mbstring intl gd opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# タイムゾーン & php.ini
ENV TZ=Asia/Tokyo
RUN echo "${TZ}" > /etc/timezone
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html
