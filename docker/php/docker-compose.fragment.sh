#!/usr/bin/sh

cat <<EOF
    php:
        build: docker/php
EOF


# If the user wants a DB docker container
if echo "${DOCKER}" | grep -Fvq '"db"'; then
    cat <<EOF
        depends_on:
            - db
EOF
fi

cat <<EOF
        restart: always
        tty: true
        ports:
            - ${PHP_PORT}:9000
        volumes:
            # Entrypoint
            - ./docker/php/entrypoint.sh:/entrypoint.sh
            - ./docker/db/wait_for_db.sh:/wait_for_db.sh
            - ./docker/social/install.sh:/var/entrypoint.d/social_install.sh
            # Main files
            - .:/var/www/social
        env_file:
            - ./docker/social/social.env
            - ./docker/db/db.env
        command: /entrypoint.sh

EOF
