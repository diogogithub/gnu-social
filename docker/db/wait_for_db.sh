#!/bin/sh

case $DBMS in
    "postgres")
        CMD="pg_isready -hdb -q -Upostgres"
        ;;
    "mariadb")
        CMD="mysqladmin ping --silent -hdb -uroot -p${MYSQL_ROOT_PASSWORD}"
        ;;
    *)
        exit 1
esac

while ! $CMD;
do
    echo "Waiting for DB..."
    sleep 3
done
