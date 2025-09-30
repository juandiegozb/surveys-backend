FROM php:8.3-fpm-alpine

RUN apk add --no-cache git bash icu-dev libzip-dev oniguruma-dev libpq-dev libxml2-dev zlib-dev curl-dev $PHPIZE_DEPS \
&& docker-php-ext-install intl pdo pdo_mysql pcntl zip bcmath \
&& pecl install redis \
&& docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN docker-php-ext-install opcache
COPY php.ini /usr/local/etc/php/conf.d/php.ini

WORKDIR /var/www/html
