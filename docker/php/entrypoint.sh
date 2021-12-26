#!/bin/sh

ln -sf /usr/local/bin/php /usr/bin/php8

/wait_for_db.sh

echo "Got response from DB"

for script in /var/entrypoint.d/*.sh; do
    $script
    ret=$?
    if [ $ret -eq 64 ]; then
        exit 0
    elif [ $ret -eq 65 ]; then
        exit 1
    fi
done

exec php-fpm
