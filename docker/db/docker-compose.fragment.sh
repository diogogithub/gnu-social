#!/usr/bin/sh

if [ "${DBMS}" = 'postgres' ]; then
    cat <<EOF
    db:
        image: postgres:alpine
        restart: always
        tty: false
        ports:
            - 5432:5432
        environment:
            - PGDATA=/var/lib/postgres/data
        env_file:
            - ./docker/db/db.env
        volumes:
            - database:/var/lib/postgres/data

EOF
else
    cat <<EOF
    db:
        image: mariadb
        restart: always
        tty: false
        ports:
            - 3306:3306
        env_file:
            - ./docker/db/db.env

EOF
fi
