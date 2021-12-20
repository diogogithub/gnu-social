#!/bin/sh

cd /var/www/social || exit 65
while :; do
    bin/console messenger:consume high low -vv --limit=10 --memory-limit=128M --time-limit=3600
done
