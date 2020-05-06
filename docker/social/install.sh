#!/bin/sh

PGPASSWORD="${POSTGRES_PASSWORD}" psql -ltq -Upostgres -hdb | \
    cut -d '|' -f1 | grep -wq "${SOCIAL_DB}"

if [ ! $? ]; then

    echo ${SOCIAL_DB}

    echo -e "Installing GNU social\nInstalling composer dependencies"

    cd /var/www/social

    composer install

    chmod g+w -R .
    chown -R :www-data .

    php bin/console doctrine:database:create || exit 1

    echo "GNU social is installed"
fi
