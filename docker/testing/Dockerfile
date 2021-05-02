FROM gsocial/php

WORKDIR /var/www/social

RUN apk update \
    && apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug
