FROM php:fpm-alpine

RUN apk update && apk add gettext-dev icu-dev zlib-dev libpng-dev gmp-dev postgresql-dev composer > /dev/null

ARG exts=" bcmath exif gd gettext gmp intl mysqli opcache pdo_mysql"

RUN apk add --virtual .phpize-deps $PHPIZE_DEPS \
        && rm -rf /usr/share/php7 \
        && rm -rf /tmp/* \
        && apk del .phpize-deps > /dev/null

RUN docker-php-ext-install ${exts}
