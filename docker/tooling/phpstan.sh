#!/usr/bin/env sh

cd /var/www/social || exit 1

vendor/bin/phpstan --ansi --no-interaction --memory-limit=2G analyse src tests components plugins
