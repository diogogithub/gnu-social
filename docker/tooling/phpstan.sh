#!/usr/bin/env sh

cd /var/www/social || exit 1


ARGS=$(echo "$*" | sed 's#\(/[^/]\+\)*/phpstan\.neon#phpstan.neon#') # Remove absolute path to config file

rm -rf /var/www/social/var/cache/*

if [ "$#" -eq 0 ]; then
    PHPSTAN_BOOT_KERNEL=1 vendor/bin/phpstan --ansi --no-interaction --memory-limit=2G analyse
else
    PHPSTAN_BOOT_KERNEL=1 vendor/bin/phpstan $ARGS
fi

rm -rf /var/www/social/var/cache/*
