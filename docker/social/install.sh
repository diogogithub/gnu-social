#!/bin/sh

case "${DBMS}" in
    'postgres')
        PGPASSWORD="${POSTGRES_PASSWORD}" psql -ltq -Upostgres -hdb | \
            cut -d '|' -f1 | grep -wq "${SOCIAL_DB}"
        DB_EXISTS=$?
        DB_TYPE='pgsql'
        SOCIAL_USER=postgres
        ;;
    'mariadb')
        mysqlcheck -cqs -uroot -p${MYSQL_ROOT_PASSWORD} -hdb social 2> /dev/null
        DB_EXISTS=$?
        DB_TYPE='mysql'
        ;;
    *)
        echo "Unknown DBMS"
        exit 1
esac

if [ ! ${DB_EXISTS} -o ! -e /var/www/social/config.php ]; then

    echo -e "Installing GNU social\nInstalling composer dependencies"

    cd /var/www/social

    composer install

    chmod g+w -R .
    chown -R :www-data .

    php /var/www/social/scripts/install_cli.php --dbtype="${DB_TYPE}" --server="${SOCIAL_DOMAIN}" --sitename="${SOCIAL_SITENAME}" \
        --host=db --fancy=yes --database="${SOCIAL_DB}" \
        --username="${SOCIAL_USER}" --password="${SOCIAL_PASSWORD}" \
        --admin-nick="${SOCIAL_ADMIN_NICK}" --admin-pass="${SOCIAL_ADMIN_PASSWORD}" || exit 1

    echo "GNU social is installed"
fi
