FROM php:fpm-alpine

RUN apk update && apk add git autoconf make file gettext-dev icu-dev zlib-dev libpng-dev gmp-dev \
        mariadb-dev mariadb-client postgresql-dev postgresql-client composer > /dev/null

ARG exts=" bcmath exif gd gettext gmp intl mysqli opcache pdo pdo_mysql mysqli pdo_pgsql pgsql"

RUN apk add --virtual .phpize-deps $PHPIZE_DEPS \
        && cd /tmp && git clone https://github.com/krakjoe/apcu && cd apcu && phpize && ./configure --enable-apcu && make install \
        && cd /tmp && git clone https://github.com/php-ds/ext-ds && cd ext-ds && phpize && ./configure && make install \
        && cd /tmp && git clone https://github.com/msgpack/msgpack-php && cd msgpack-php && phpize  && ./configure && make install \
        && cd /tmp && git clone https://github.com/lz4/lz4 && cd lz4 && make install \
        && cd /tmp && git clone https://github.com/phpredis/phpredis && cd phpredis && phpize  && ./configure --enable-redis-msgpack --enable-redis-lz4 --with-liblz4=yes && make install \
        && rm -rf /usr/share/php7 \
        && rm -rf /tmp/* \
        && apk del .phpize-deps > /dev/null

RUN docker-php-ext-install ${exts} && docker-php-ext-enable ds msgpack redis apcu

WORKDIR /var/www/social
