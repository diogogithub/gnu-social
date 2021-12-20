#!/usr/bin/sh

if [ "${BUILD_PHP}" -ne 0 ]; then
    cat <<EOF
    worker:
        build: docker/php
EOF
else
    cat <<EOF
    worker:
        image: gsocial/php
EOF
fi

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
        volumes:
            # Entrypoint
            - ./docker/php/entrypoint.sh:/entrypoint.sh
            - ./docker/db/wait_for_db.sh:/wait_for_db.sh
            - ./docker/social/install.sh:/var/entrypoint.d/social_install.sh
            - ./docker/worker/worker.sh:/var/entrypoint.d/social_worker.sh
            # Main files
            - .:/var/www/social
            - /var/www/social/docker # exclude docker folder
        env_file:
            - ./docker/social/social.env
            - ./docker/db/db.env
        command: /entrypoint.sh

EOF
