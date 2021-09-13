#!/usr/bin/env sh

cd /var/www/social || exit 1

rm -rf /var/www/social/var/cache/*
PHPSTAN_BOOT_KERNEL=1 vendor/bin/phpstan --ansi --no-interaction --memory-limit=2G analyse
rm -rf /var/www/social/var/cache/*
