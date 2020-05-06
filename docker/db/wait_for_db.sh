#!/bin/sh

case $DBMS in
    "mariadb")
        CMD="mysqladmin ping --silent -hdb -uroot -p${MYSQL_ROOT_PASSWORD}"
        ;;
    "postgres")
        CMD="pg_isready -hdb -q -Upostgres"
        ;;
    *)
        exit 1
esac

while ! $CMD;
do
    echo "Waiting for DB..."
    sleep 3
done
