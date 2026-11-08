# Laravel 13 + Octane on Swoole.
FROM php:8.4-cli-alpine
RUN apk add --no-cache $PHPIZE_DEPS linux-headers openssl-dev brotli-dev \
 && pecl install swoole \
 && docker-php-ext-enable swoole \
 && docker-php-ext-install pdo_mysql pcntl opcache \
 && apk del $PHPIZE_DEPS
RUN apk add --no-cache libstdc++ brotli-libs
RUN { echo 'opcache.enable_cli=1'; echo 'opcache.validate_timestamps=0'; } > /usr/local/etc/php/conf.d/opcache-prod.ini
WORKDIR /app
