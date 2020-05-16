#!/bin/sh

case "${DBMS}" in
    'postgres')
        PGPASSWORD="${POSTGRES_PASSWORD}" psql -ltq -Upostgres -hdb | \
            cut -d '|' -f1 | grep -Fwq "${SOCIAL_DB}"
        DB_EXISTS=$?
        ;;
    'mariadb')
        mysqlcheck -cqs -uroot -p"${MYSQL_ROOT_PASSWORD}" -hdb social 2> /dev/null
        DB_EXISTS=$?
        exit 1
        ;;
    *)
        echo "Unknown DBMS"
        exit 1
esac

if [ ${DB_EXISTS} -ne 0 ]; then

    echo "Installing GNU social"
    echo "Installing composer dependencies"

    cd /var/www/social || exit 1

    composer install

    chmod g+w -R .
    chown -R :www-data .

    php bin/console doctrine:database:create || exit 1
    php bin/console doctrine:schema:create   || exit 1

    echo "GNU social is installed"
else
    echo "GNU social is already installed"
fi
