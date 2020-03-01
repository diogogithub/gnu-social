FROM php:fpm-alpine

RUN apk update && apk add gettext-dev icu-dev zlib-dev libpng-dev gmp-dev postgresql-dev

ARG exts=" bcmath exif gd gettext gmp intl mysqli opcache pdo pdo_pgsql pgsql"

ARG MEMCACHED_DEPS="zlib-dev libmemcached-dev cyrus-sasl-dev"
RUN apk add libmemcached-libs zlib
RUN apk add --virtual .phpize-deps $PHPIZE_DEPS \
        && apk add --virtual .memcached-deps $MEMCACHED_DEPS \
        && pecl install memcached \
        && echo "extension=memcached.so" > /usr/local/etc/php/conf.d/20_memcached.ini \
        && rm -rf /usr/share/php7 \
        && rm -rf /tmp/* \
        && apk del .memcached-deps .phpize-deps

RUN docker-php-ext-install ${exts}
