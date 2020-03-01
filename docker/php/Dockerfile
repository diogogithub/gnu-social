FROM php:fpm-alpine

RUN apk update && apk add gettext-dev icu-dev zlib-dev libpng-dev gmp-dev postgresql-dev

ARG exts="bcmath exif gd gettext gmp intl mysqli opcache pdo pdo_pgsql pgsql"

RUN docker-php-ext-install ${exts} \
    && docker-php-ext-enable ${exts}
