#!/usr/bin/sh

cat <<EOF
    redis:
        image: redis:alpine
        restart: always
        tty: false
        ports:
            - 6379:6379

EOF
