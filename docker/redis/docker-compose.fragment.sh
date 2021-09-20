#!/usr/bin/sh

cat <<EOF
    redis:
        image: redis:alpine
        restart: always
        tty: false
        volumes:
            - ./docker/redis/redis.conf:/etc/redis/redis.conf
        ports:
            - 6379:6379
        command: redis-server /etc/redis/redis.conf

EOF
