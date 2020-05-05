#!/bin/sh

/wait_for_db.sh

echo "Got response from DB"

for script in /var/entrypoint.d/*.sh; do
    $script
done

exec php-fpm
