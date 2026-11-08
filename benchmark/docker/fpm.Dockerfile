# Baseline: Lumen on PHP-FPM with OPcache tuned for production (no timestamp checks).
FROM php:8.4-fpm-alpine
RUN docker-php-ext-install pdo_mysql opcache
RUN { \
      echo 'opcache.enable=1'; \
      echo 'opcache.validate_timestamps=0'; \
      echo 'opcache.memory_consumption=128'; \
      echo 'opcache.max_accelerated_files=20000'; \
    } > /usr/local/etc/php/conf.d/opcache-prod.ini \
 && { \
      echo '[www]'; \
      echo 'pm = static'; \
      echo 'pm.max_children = 8'; \
    } > /usr/local/etc/php-fpm.d/zz-bench.conf
WORKDIR /app
